<?php

namespace App\Services\Api\Payment;

use App\Enums\FinancialStatus;
use App\Enums\TransactionType;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Repositories\IGatewayPaymentRepositories;
use App\Repositories\ITransactionRepositories;
use App\Repositories\IWalletRepositories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConsultationWebhookService
{
    public function __construct(
        private readonly IGatewayPaymentRepositories $gatewayPayments,
        private readonly ITransactionRepositories $transactions,
        private readonly IWalletRepositories $wallets,
    ) {}

    public function processWebhook(array $payload): void
    {
        $this->validatePayload($payload);

        DB::transaction(function () use ($payload): void {
            $gatewayPayment = $this->gatewayPayments->findByReference($payload['MerchantReference']);

            if (!$gatewayPayment) {
                throw new HttpException(404, 'Gateway payment not found.');
            }

            if ($this->isFinalStatus((string) $gatewayPayment->status)) {
                Log::channel('financial')->info('consultation_webhook.ignored_final_status', [
                    'gateway_payment_id' => $gatewayPayment->id,
                    'status' => $gatewayPayment->status,
                ]);

                return;
            }

            $this->assertHashIsValid($payload);
            $this->assertCurrencyAndAmount($gatewayPayment->amount, $payload);
            $this->assertSystemReferenceIsUnique($payload['SystemReference']);
            $this->assertInitiatedLock($gatewayPayment->initiated_lock, $gatewayPayment->reference_type, $gatewayPayment->reference_id);

            if ((string) $payload['ResponseCode'] === '00') {
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

        $consultantWallet = $this->wallets->getByOwner($consultation->consultant_id);

        $this->transactions->create([
            'reference_type' => $gatewayPayment->reference_type,
            'reference_id' => $gatewayPayment->reference_id,
            'gateway_payment_id' => $gatewayPayment->id,
            'transaction_type' => TransactionType::PAYMENT_RECORD->value,
            'entry_type' => 'debit',
            'wallet_id' => null,
            'gross_amount' => $gatewayPayment->amount,
            'platform_commission' => 0,
            'vat_amount' => 0,
            'net_amount' => $gatewayPayment->amount,
            'currency' => (string) $gatewayPayment->currency,
            'status' => 'available',
            'meta' => [
                'role' => 'patient',
                'system_reference' => (string) $payload['SystemReference'],
                'response_code' => (string) $payload['ResponseCode'],
            ],
        ]);

        $this->transactions->create([
            'reference_type' => $gatewayPayment->reference_type,
            'reference_id' => $gatewayPayment->reference_id,
            'gateway_payment_id' => $gatewayPayment->id,
            'transaction_type' => TransactionType::CONSULTATION_CREDIT->value,
            'entry_type' => 'credit',
            'wallet_id' => $consultantWallet->id,
            'gross_amount' => $gatewayPayment->amount,
            'platform_commission' => 0,
            'vat_amount' => 0,
            'net_amount' => $gatewayPayment->amount,
            'currency' => (string) $gatewayPayment->currency,
            'status' => 'pending',
            'meta' => [
                'role' => 'consultant',
                'consultant_id' => $consultation->consultant_id,
                'system_reference' => (string) $payload['SystemReference'],
            ],
        ]);

        $this->wallets->increasePendingBalance($consultantWallet, (float) $gatewayPayment->amount);

        $consultation->update([
            'financial_status' => FinancialStatus::HELD->value,
        ]);

        $this->gatewayPayments->update([
            'status' => 'captured',
            'gateway_transaction_id' => (string) $payload['SystemReference'],
            'response_code' => (string) $payload['ResponseCode'],
            'response_message' => (string) ($payload['Message'] ?? 'AUTHORIZED'),
            'payload' => $payload,
            'initiated_lock' => null,
        ], $gatewayPayment->id);
    }

    public function processFailure($gatewayPayment, array $payload): void
    {
        $updateData = [
            'status' => 'failed',
            'response_code' => (string) ($payload['ResponseCode'] ?? ''),
            'response_message' => (string) ($payload['Message'] ?? 'FAILED'),
            'payload' => $payload,
            'initiated_lock' => null,
        ];

        $attempts = isset($gatewayPayment->attempts) ? (int) $gatewayPayment->attempts + 1 : null;
        if ($attempts !== null) {
            $updateData['attempts'] = $attempts;
        }

        $this->gatewayPayments->update($updateData, $gatewayPayment->id);

        if ($attempts !== null && isset($gatewayPayment->max_attempts) && $attempts >= (int) $gatewayPayment->max_attempts) {
            $consultation = $gatewayPayment->reference;
            if ($consultation && $this->isConsultationReference($consultation::class)) {
                $consultation->update(['financial_status' => FinancialStatus::FROZEN->value]);
            }
        }
    }

    private function validatePayload(array $payload): void
    {
        $required = ['MerchantReference', 'ResponseCode', 'SystemReference', 'Amount', 'CurrencyId', 'SecureHash'];

        foreach ($required as $key) {
            if (!array_key_exists($key, $payload)) {
                throw new HttpException(422, "Missing required field: {$key}");
            }
        }
    }

    private function assertHashIsValid(array $payload): void
    {
        $received = strtoupper((string) $payload['SecureHash']);
        $expected = $this->generateSecureHashForWebhook($payload);

        if (!hash_equals($expected, $received)) {
            throw new HttpException(401, 'Invalid secure hash.');
        }
    }

    private function assertCurrencyAndAmount(string $expectedAmount, array $payload): void
    {
        if ((int) $payload['CurrencyId'] !== 512) {
            throw new HttpException(422, 'Unsupported currency id.');
        }

        $payloadAmount = round((float) $payload['Amount'], 3);
        $databaseAmount = round((float) $expectedAmount, 3);

        if ($payloadAmount !== $databaseAmount) {
            throw new HttpException(422, 'Amount mismatch.');
        }
    }

    private function assertSystemReferenceIsUnique(string $systemReference): void
    {
        $existing = $this->transactions->findByMeta('system_reference', $systemReference);

        if ($existing !== null) {
            throw new HttpException(409, 'Duplicate system reference.');
        }
    }

    private function assertInitiatedLock(?string $initiatedLock, string $referenceType, int $referenceId): void
    {
        if ($initiatedLock === null) {
            return;
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
        $filtered = array_map(fn ($v) => $v === null ? '' : (string) $v, $filtered);

        ksort($filtered);

        $baseString = collect($filtered)
            ->map(fn ($v, $k) => "{$k}={$v}")
            ->implode('&');

        $binaryKey = hex2bin(config('amwal.secure_key'));

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
