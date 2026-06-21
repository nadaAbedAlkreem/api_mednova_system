<?php

namespace App\Services\Api\Payment;

use App\Enums\AmountStatus;
use App\Enums\EntryType;
use App\Enums\FinancialStatus;
use App\Enums\GatewayPaymentStatus;
use App\Enums\StatusType;
use App\Enums\TransactionType;
use App\Events\ConsultationRequested;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Repositories\IGatewayPaymentRepositories;
use App\Repositories\ITransactionRepositories;
use App\Repositories\IWalletRepositories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

readonly class ConsultationWebhookService
{
    public function __construct(
        private IGatewayPaymentRepositories $gatewayPayments,
        private ITransactionRepositories    $transactions,
        private IWalletRepositories         $wallets)
    {
    }

    public function processWebhook(array $payload): void
    {
        Log::channel('financial')->info('consultation_webhook', ['payload' => $payload]);
        $this->validatePayload($payload);
        DB::transaction(function () use ($payload): void {
            $gatewayPayment = $this->gatewayPayments->findByReference($payload['MerchantReference']);
            Log::channel('financial')->info('$gatewayPayment', ['$gatewayPayment' => $gatewayPayment,]);
            if (!$gatewayPayment) {
                throw new HttpException(404, 'Gateway payment not found.');
            }

            if ($this->isFinalStatus((string)$gatewayPayment->status)) {
                Log::channel('financial')->info('consultation_webhook.ignored_final_status', [
                    'gateway_payment_id' => $gatewayPayment->id,
                    'status' => $gatewayPayment->status,
                ]);
                return;
            }
            Log::channel('financial')->info('$gatewayPayment', ['bool' => true]);
            $this->assertHashIsValid($payload);
            $this->assertMidMerchantValid($payload);
            $this->assertTxnTypeIsValid($payload);
            $this->assertPaidThrough($payload);
            $this->assertCurrencyAndAmount($gatewayPayment->amount, $payload);
            $this->assertSystemReferenceIsUnique($payload['SystemReference']);
            $this->assertInitiatedLock($gatewayPayment->initiated_lock, $gatewayPayment->reference_type, $gatewayPayment->reference_id);

            if ((string)$payload['ResponseCode'] === '00') {
                $this->processSuccess($gatewayPayment, $payload);

                return;
            }

            $this->processFailure($gatewayPayment, $payload);
        });
    }

    public function processSuccess($gatewayPayment, array $payload): void
    {
        $consultation = $gatewayPayment->reference;
        if (!$consultation || !$this->isConsultationReference($consultation::class)) {
            throw new HttpException(422, 'Invalid consultation reference on payment.');
        }

        if ((string)$consultation->financial_status === FinancialStatus::HELD->value) {
            Log::channel('financial')->info('consultation_webhook.ignored_already_held', [
                'consultation_id'    => $consultation->id,
                'gateway_payment_id' => $gatewayPayment->id,
            ]);
            return;
        }

        $updated = $this->gatewayPayments->updateWhere([
            'status' => GatewayPaymentStatus::CAPTURED->value,
            'gateway_transaction_id' => (string)$payload['SystemReference'],
            'response_code' => (string)$payload['ResponseCode'],
            'response_message' => (string)($payload['Message'] ?? 'AUTHORIZED'),
            'payload' => $payload,
            'initiated_lock' => null,], ['id' => $gatewayPayment->id, 'status' => GatewayPaymentStatus::INITIATED->value]);
        if (!$updated) {
            return;
        }

//        $consultantWallet = $this->wallets->getByOwner($consultation->consultant_id);
        // مهم: جلب محفظة المنصة وليس محفظة المستشار
        $platformWallet = $this->wallets->getPlatformWallet();

        if (!$platformWallet) {
            throw new HttpException(500, 'Platform wallet not found.');
        }
        $this->transactions->create([
            'reference_type' => $gatewayPayment->reference_type,
            'reference_id' => $gatewayPayment->reference_id,
            'gateway_payment_id' => $gatewayPayment->id,
            'transaction_type' => TransactionType::PAYMENT_RECORD->value,
            'entry_type' => EntryType::ENTRY_DEBIT->value ?? 'debit',
            'wallet_id' => null,
            'gross_amount' => (float)$gatewayPayment->amount, // ما دفعه المريض
            'platform_commission' => 0,
            'vat_amount' => 0,
            'net_amount' => (float) ($gatewayPayment->net_received_amount ?? $consultation->consultation_price),
            'currency' => (string)$gatewayPayment->currency,
            'status' => AmountStatus::STATUS_AVAILABLE->value ?? 'available',
            'meta' => [
                'role' => 'patient',
                'funding_source' => 'external_gateway',
                'wallet_impact' => 'none',
                'system_reference' => (string)$payload['SystemReference'],
                'response_code' => (string)$payload['ResponseCode'],
                'consultation_price' => (float)$consultation->consultation_price,
                'gateway_fee_rate' => (float)$consultation->gateway_commission_rate,
                'gateway_fee_amount' => (float)$consultation->gateway_commission_amount,
                'platform_commission_rate' => (float)$consultation->platform_commission_rate,
                'platform_commission_amount' => (float)$consultation->platform_commission_amount,
                'consultant_earning_amount' => (float)$consultation->consultant_earning_amount,
                'gross_paid_amount' => (float)$gatewayPayment->amount,
                'net_received_amount' => (float) ($gatewayPayment->net_received_amount ?? $consultation->consultation_price),
                ],
        ]);
        $this->transactions->create([
            'reference_type' => $gatewayPayment->reference_type,
            'reference_id' => $gatewayPayment->reference_id,
            'gateway_payment_id' => $gatewayPayment->id,
            'transaction_type' => TransactionType::CONSULTATION_HOLD->value,
            'entry_type' => EntryType::ENTRY_CREDIT->value ?? 'credit',
            'wallet_id' => $platformWallet->id,
            'gross_amount' => (float)$consultation->consultation_price,
            'platform_commission' => 0,
            'vat_amount' => 0,
            'net_amount' => (float)$consultation->consultation_price,
            'currency' => (string)$gatewayPayment->currency,
            'status' => AmountStatus::STATUS_PENDING->value ?? 'pending',
            'meta' => [
                'role' => 'platform_holding',
                'consultation_id' => $consultation->id,
                'consultant_id' => $consultation->consultant_id,
                'patient_id' => $consultation->patient_id,
                'system_reference' => (string)$payload['SystemReference'],
                'holding_reason' => 'consultation_payment_received',
                'platform_commission_rate' => (float)$consultation->platform_commission_rate,
                'platform_commission_amount' => (float)$consultation->platform_commission_amount,
                'consultant_earning_amount' => (float)$consultation->consultant_earning_amount,
                'gross_paid_amount' => (float)$gatewayPayment->amount,
            ],
        ]);

        $this->wallets->increasePendingBalance($platformWallet,
            (float)$consultation->consultation_price);

        $consultation->update([
            'financial_status' => FinancialStatus::HELD->value,
        ]);
        $message = __('messages.new_consultation_notify', [
            'name' => $consultation->patient->full_name
        ]);
        event(new ConsultationRequested($consultation, $message, 'requested'));

//        $message = __('messages.ACCEPTED_REQUEST', [
//            'name' => $consultation->consultant->full_name,
//        ]);
//        event(new ConsultationRequested($consultation, $message, 'accepted'));

//        try {
//
//        } catch (\Illuminate\Database\QueryException $e) {
//            if (str_contains($e->getMessage(), 'gateway_transaction_id')) {
//                throw new HttpException(409, 'Duplicate system reference');
//            }
//            throw $e;
//        }
    }

    public function processFailure($gatewayPayment, array $payload): void
    {
        $attempts = isset($gatewayPayment->attempts) ? (int)$gatewayPayment->attempts + 1 : null;

        $updateData = [
            'status' => 'failed',
            'response_code' => (string)($payload['ResponseCode'] ?? ''),
            'response_message' => (string)($payload['Message'] ?? 'FAILED'),
            'payload' => $payload,
            'initiated_lock' => null,
            'attempts' => $attempts,
        ];
        $this->gatewayPayments->update($updateData, $gatewayPayment->id);

        if ($attempts !== null && isset($gatewayPayment->max_attempts) && $attempts >= (int)$gatewayPayment->max_attempts) {
            $consultation = $gatewayPayment->reference;
            if ($consultation && $this->isConsultationReference($consultation::class)) {
                $suspensionCount = (int)$consultation->suspension_count + 1;
                $freezeMinutes = match (true) {
                    $suspensionCount === 1 => 30,
                    $suspensionCount === 2 => 120,
                    default => 1440, // 24 ساعة
                };
                $this->gatewayPayments->update([
                    'frozen_at' => now(),
                    'freeze_until' => now()->addMinutes($freezeMinutes),
                    'freeze_reason' => 'max_attempts_reached',
                ], $gatewayPayment->id);
                $consultation->update([
                    'financial_status' => FinancialStatus::PAYMENT_SUSPENDED->value,
                    'suspended_until' => now()->addMinutes($freezeMinutes),
                    'suspension_count' => $suspensionCount,
                ]);
                Log::channel('financial')->warning('consultation.payment_suspended', [
                    'consultation_id' => $consultation->id,
                    'suspension_count' => $suspensionCount,
                    'suspended_until' => now()->addMinutes($freezeMinutes),
                    'freeze_minutes' => $freezeMinutes,
                ]);
            }
        }
    }

    private function validatePayload(array $payload): void
    {
        $required = ['MerchantReference', 'ResponseCode', 'SystemReference', 'Amount', 'CurrencyId', 'SecureHash'];

        foreach ($required as $key) {
            if (!array_key_exists($key, $payload)) {
                throw new HttpException(422, message: "Missing required field: {$key}");
            }
        }
    }

    private function assertHashIsValid(array $payload): void
    {
        $received = strtoupper((string)$payload['SecureHash']);
        $expected = $this->generateSecureHashForWebhook($payload);

        if (!hash_equals($expected, $received)) {
            throw new HttpException(401, 'Invalid secure hash.');
        }
    }

    private function assertTxnTypeIsValid(array $payload): void
    {
        if ($payload['TxnType'] !== 'Purchase') {
            throw new HttpException(422, 'Invalid transaction type');
        }
    }

    private function assertPaidThrough(array $payload): void
    {
        if ($payload['PaidThrough'] !== 'Card') {
            throw new HttpException(422, 'Unsupported payment method');
        }
    }

    private function assertMidMerchantValid(array $payload): void
    {
        if ((int)$payload['MerchantId'] !== (int)(config('amwal.mid'))) {
            throw new HttpException(403, 'Invalid merchant');
        }
    }

    private function assertCurrencyAndAmount(string $expectedAmount, array $payload): void
    {
        if ((int)$payload['CurrencyId'] !== 512) {
            throw new HttpException(422, 'Unsupported currency id.');
        }

        $payloadAmount = round((float)$payload['Amount'] / 1000, 3);
        $databaseAmount = round((float)$expectedAmount, 3);

        if ($payloadAmount !== $databaseAmount) {
            Log::channel('financial')->error('amount_mismatch', [
                'payload_amount' => $payloadAmount,
                'database_amount' => $databaseAmount,
            ]);
            throw new HttpException(422, 'Amount mismatch.');
        }
    }

    private function assertSystemReferenceIsUnique(string $systemReference): void
    {
        $existing = $this->gatewayPayments->existsBySystemReference($systemReference);
        if ($existing === true) {
            throw new HttpException(409, 'Duplicate system reference.');
        }
    }

    private function assertInitiatedLock(?string $initiatedLock, string $referenceType, int $referenceId): void
    {
        if ($initiatedLock === null) {
            throw new HttpException(423, 'Missing payment lock.');
        }

        $expected = $referenceType . '-' . $referenceId;

        if ($initiatedLock !== $expected) {
            throw new HttpException(423, 'Payment lock mismatch.');
        }
    }

    private function generateSecureHashForWebhook(array $payload): string
    {
        $allowedKeys = [
            'MerchantId',
            'TerminalId',
            'AuthorizationDateTime',
            'DateTimeLocalTrxn',
            'ResponseCode',
            'TxnType',
            'PaidThrough',
            'SystemReference',
            'Message',
            'MerchantReference',
            'Amount',
            'CurrencyId',
        ];

        $filtered = array_intersect_key($payload, array_flip($allowedKeys));
        $filtered = array_map(fn($v) => $v === null ? '' : (string)$v, $filtered);

        ksort($filtered);

        $baseString = collect($filtered)
            ->map(fn($v, $k) => "{$k}={$v}")
            ->implode('&');

        Log::channel('financial')->debug('webhook_hash_base_string', [
            'base_string' => $baseString,
        ]);

        $binaryKey = hex2bin(config('amwal.secure_key'));
        Log::channel('financial')->debug('$binaryKey', [
            '$binaryKey' => $binaryKey,
        ]);
        return strtoupper(hash_hmac('sha256', $baseString, $binaryKey));
    }

    private function isFinalStatus(string $status): bool
    {
        return in_array($status, ['captured', 'failed', 'refunded', 'expired'], true);
    }

    private function isConsultationReference(string $type): bool
    {
        return in_array($type, [ConsultationChatRequest::class, ConsultationVideoRequest::class], true);
    }
}
