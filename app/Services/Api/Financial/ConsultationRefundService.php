<?php

namespace App\Services\Api\Financial;

use App\DTOs\Financial\ManualRefundMeta;
use App\Enums\AmountStatus;
use App\Enums\EntryType;
use App\Enums\FinancialStatus;
use App\Enums\TransactionType;
use App\Exceptions\ConsultantWalletNotFoundException;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Exceptions\InvalidRefundAmountException;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Models\Transaction;
use App\Repositories\IWalletRepositories;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;


class ConsultationRefundService
{
    protected IWalletRepositories $walletRepositories;
    protected FinancialTransactionService $financialTransactionService;

    public function __construct(IWalletRepositories $wallets, FinancialTransactionService $financialTransactionService , )
    {
        $this->walletRepositories = $wallets;
        $this->financialTransactionService = $financialTransactionService;
    }

    /**
     * تنفيذ الاسترداد الداخلي لاستشارة ملغاة من قِبل المريض.
     *
     * يفترض أن يُستدعى هذا الميثود:
     *   - داخل DB::transaction() نشطة
     *   - بعد lockForUpdate() على سجل الاستشارة
     * @throws InvalidRefundAmountException
     */
    public function processInternalRefund($consultation ,string $reason = 'manual_refund'): void
    {
        // ---------------------------------------------------------------
        // 1. Guard: الحالة المالية يجب أن تكون HELD
        // ---------------------------------------------------------------
        if (!in_array($consultation->financial_status, [
            FinancialStatus::HELD->value,
            FinancialStatus::FROZEN->value,
        ], true)) {
            Log::channel('financial')->info('consultation.internal_refund_skipped', [
                'consultation_id' => $consultation->id,
                'financial_status' => $consultation->financial_status,
                'reason' => 'status_not_refundable',
                'trace_id' => request()->header('X-Trace-ID'),
            ]);
            return;
        }

        // ---------------------------------------------------------------
        // 2. Guard: Idempotency عبر الـ Ledger (يدعم الـ DB Unique Constraint)
        // ---------------------------------------------------------------
        if ($this->refundAlreadyExists($consultation)) {
            Log::channel('financial')->warning('consultation.internal_refund_skipped', [
                'consultation_id' => $consultation->id,
                'reason' => 'refund_transaction_exists',
                'trace_id' => request()->header('X-Trace-ID'),
            ]);
            return;
        }

        // ---------------------------------------------------------------
        // 3. التحقق من المبلغ
        // ---------------------------------------------------------------
        $refundAmount = $this->resolveRefundAmount($consultation);

        // ---------------------------------------------------------------
        // 4. قفل المحافظ بترتيب ثابت (تصاعدي بالـ ID) لمنع الـ Deadlock
        // ---------------------------------------------------------------
//        [$firstOwnerId, $secondOwnerId] = $this->orderedOwnerIds($consultation->consultant_id, $consultation->patient_id);
//        $firstWallet = $this->walletRepositories->findByOwnerForUpdate($firstOwnerId);
//        $secondWallet = $this->walletRepositories->findByOwnerForUpdate($secondOwnerId);
//
//        // تحديد أيهما المستشار وأيهما المريض بعد الترتيب
//        [$consultantWallet, $patientWallet] = $consultation->consultant_id === $firstOwnerId
//            ? [$firstWallet, $secondWallet]
//            : [$secondWallet, $firstWallet];

        // ---------------------------------------------------------------
        // 5. التحقق من وجود المحافظ
        // ---------------------------------------------------------------
        $platformWallet = $this->walletRepositories->getPlatformWallet();
//        if ($platformWallet === null) {
//            throw new HttpException(500, 'Platform wallet not found.');
//        }

        $patientWallet = $this->walletRepositories->getOrCreateByOwnerForUpdate($consultation->patient_id);
        if ($patientWallet === null) {
            throw new HttpException(500, 'Patient wallet could not be created.');
        }

//        if ($patientWallet === null) {
//            Log::channel('financial')->critical('consultation.internal_refund_failed', [
//                'reason' => 'patient_wallet_not_found',
//                'consultation_id' => $consultation->id,
//                'patient_id' => $consultation->patient_id,
//                'trace_id' => request()->header('X-Trace-ID'),
//            ]);
//
//            $patientWallet = $this->walletRepositories->getOrCreateByOwnerForUpdate($consultation->patient_id);
//        }

        // ---------------------------------------------------------------
        // 6. التحقق من كفاية الرصيد
        // ---------------------------------------------------------------
        if ((float) $platformWallet->pending_balance < $refundAmount) {
            Log::channel('financial')->critical('consultation.internal_refund_failed', [
                'reason' => 'insufficient_platform_pending_balance',
                'consultation_id' => $consultation->id,
                'refund_amount' => $refundAmount,
                'platform_pending_balance' => $platformWallet->pending_balance,
                'platform_wallet_id' => $platformWallet->id,
                'trace_id' => request()->header('X-Trace-ID'),
            ]);

            throw new HttpException(409, 'Insufficient platform pending balance for refund.');
        }


        // ---------------------------------------------------------------
        // 7. تسجيل القيود المحاسبية أولاً (Ledger is the source of truth)
        //    القيد المزدوج: Debit المستشار = Credit المريض
        // ---------------------------------------------------------------
        $currency = (string) ($platformWallet->currency ?? 'OMR');
        $meta = array_merge(
            ManualRefundMeta::pending($consultation)->toArray(),
            [
                'refund_reason' => $reason,
                'consultation_price' => (float) $consultation->consultation_price,
                'gateway_fee_amount' => (float) $consultation->gateway_commission_amount,
                'gross_amount' => (float) ($consultation->gross_amount ?? 0),
                'platform_commission_amount' => (float) $consultation->platform_commission_amount,
                'consultant_earning_amount' => (float) $consultation->consultant_earning_amount,
            ]
        );

        // 7.1) إخراج المبلغ من الحجز عند المنصة
        $this->financialTransactionService->createWalletEntry(
            reference: $consultation,
            gatewayPaymentId: null,
            transactionType: TransactionType::CONSULTATION_RELEASE->value,
            entryType: EntryType::ENTRY_DEBIT->value,
            walletId: $platformWallet->id,
            grossAmount: $refundAmount,
            netAmount: $refundAmount,
            currency: $currency,
            status: AmountStatus::STATUS_AVAILABLE->value,
            meta: array_merge($meta, [
                'role' => 'platform_holding',
                'release_type' => 'refund',
            ]),
            platformCommission: 0,
            vatAmount: 0,
        );
        // 7.2) إضافة المبلغ للمريض
        $this->financialTransactionService->createWalletEntry(
            reference: $consultation,
            gatewayPaymentId: null,
            transactionType: TransactionType::REFUND->value,
            entryType: EntryType::ENTRY_CREDIT->value,
            walletId: $patientWallet->id,
            grossAmount: $refundAmount,
            netAmount: $refundAmount,
            currency: $currency,
            status: AmountStatus::STATUS_AVAILABLE->value,
            meta: array_merge($meta, [
                'role' => 'patient',
                'refund_mode' => 'internal',
            ]),
            platformCommission: 0,
            vatAmount: 0,
        );
        // ---------------------------------------------------------------
        // 8. تحديث الأرصدة بعد القيود (atomic SQL — لا fetch-compute-save)
        // ---------------------------------------------------------------
        if ($consultation->financial_status === FinancialStatus::FROZEN->value) {
            $platformWallet->decrement('frozen_balance', $refundAmount);
        } else {
            $platformWallet->decrement('pending_balance', $refundAmount);
        }
        $patientWallet->increment('available_balance', $refundAmount);

        // ---------------------------------------------------------------
        // 9. تحديث الحالة المالية للاستشارة
        // ---------------------------------------------------------------
        $consultation->update([
            'financial_status' => FinancialStatus::REFUNDED_INTERNAL,
        ]);

        // ---------------------------------------------------------------
        // 10. تسجيل نجاح العملية
        // ---------------------------------------------------------------
        Log::channel('financial')->info('consultation.internal_refund_created', [
            'consultation_id' => $consultation->id,
            'amount' => $refundAmount,
            'currency' => $currency,
            'patient_id' => $consultation->patient_id,
            'consultant_id' => $consultation->consultant_id,
            'platform_wallet_id' => $platformWallet->id,
            'patient_wallet' => $patientWallet->id,
            'trace_id' => request()->header('X-Trace-ID'),
            'executed_at' => now()->toIso8601String(),
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * هل يوجد مسبقاً قيد استرداد لهذه الاستشارة؟
     * طبقة الحماية الثانية بعد فحص financial_status.
     */
    private function refundAlreadyExists($consultation): bool
    {
        return Transaction::query()
            ->where('reference_type', get_class($consultation))
            ->where('reference_id', $consultation->id)
            ->where('transaction_type', TransactionType::REFUND->value)
            ->exists();
    }

    /**
     * استخراج مبلغ الاسترداد مع التحقق الصارم.
     *
     * القرار التجاري الحالي: استرداد net_amount فقط.
     * المريض لا يسترجع العمولة والضريبة — تبقى للمنصة.
     *
     * @throws InvalidRefundAmountException
     */
    private function resolveRefundAmount($consultation): float
    {
        $amount = (float)$consultation->consultation_price;

        if ($consultation->consultation_price === null || $amount <= 0) {
            Log::channel('financial')->critical('consultation.internal_refund_failed', [
                'reason' => 'invalid_net_amount',
                'consultation_id' => $consultation->id,
                'consultation_price' => $consultation->consultation_price,
                'trace_id' => request()->header('X-Trace-ID'),
            ]);
            throw new InvalidRefundAmountException($consultation->id, $consultation->consultation_price);
        }

        return $amount;
    }

    /**
     * ترتيب تصاعدي لمعرّفات المالكين لضمان ترتيب قفل موحّد.
     * يمنع الـ Deadlock في حال تزامن عمليتين على نفس الطرفين بترتيب معكوس.
     *
     * @return array{0: int, 1: int}
     */
    private function orderedOwnerIds(int $consultantId, int $patientId): array
    {
        return $consultantId < $patientId
            ? [$consultantId, $patientId]
            : [$patientId, $consultantId];
    }


}
