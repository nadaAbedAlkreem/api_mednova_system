<?php

namespace App\Services\Api\Financial;

use App\DTOs\Financial\ManualRefundMeta;
use App\Enums\AmountStatus;
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


class ConsultationRefundService
{
    protected IWalletRepositories $walletRepositories;
    protected FinancialTransactionService $financialTransactionService;

    public function __construct(IWalletRepositories $wallets, FinancialTransactionService $financialTransactionService)
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
    public function processInternalRefund($consultation): void
    {
        // ---------------------------------------------------------------
        // 1. Guard: الحالة المالية يجب أن تكون HELD
        // ---------------------------------------------------------------
        if ($consultation->financial_status !== FinancialStatus::HELD->value) {
            Log::channel('financial')->info('consultation.internal_refund_skipped', [
                'consultation_id' => $consultation->id,
                'financial_status' => $consultation->financial_status instanceof \BackedEnum
                    ? $consultation->financial_status->value
                    : $consultation->financial_status,
                'reason' => 'status_is_not_held',
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
        $netAmount = $this->resolveRefundAmount($consultation);

        // ---------------------------------------------------------------
        // 4. قفل المحافظ بترتيب ثابت (تصاعدي بالـ ID) لمنع الـ Deadlock
        // ---------------------------------------------------------------
        [$firstOwnerId, $secondOwnerId] = $this->orderedOwnerIds($consultation->consultant_id, $consultation->patient_id);
        $firstWallet = $this->walletRepositories->findByOwnerForUpdate($firstOwnerId);
        $secondWallet = $this->walletRepositories->findByOwnerForUpdate($secondOwnerId);

        // تحديد أيهما المستشار وأيهما المريض بعد الترتيب
        [$consultantWallet, $patientWallet] = $consultation->consultant_id === $firstOwnerId
            ? [$firstWallet, $secondWallet]
            : [$secondWallet, $firstWallet];

        // ---------------------------------------------------------------
        // 5. التحقق من وجود المحافظ
        // ---------------------------------------------------------------
        if ($consultantWallet === null) {
            Log::channel('financial')->critical('consultation.internal_refund_failed', [
                'reason' => 'consultant_wallet_not_found',
                'consultation_id' => $consultation->id,
                'consultant_id' => $consultation->consultant_id,
                'trace_id' => request()->header('X-Trace-ID'),
            ]);
            throw new ConsultantWalletNotFoundException($consultation->consultant_id);
        }

        if ($patientWallet === null) {
            Log::channel('financial')->critical('consultation.internal_refund_failed', [
                'reason' => 'patient_wallet_not_found',
                'consultation_id' => $consultation->id,
                'patient_id' => $consultation->patient_id,
                'trace_id' => request()->header('X-Trace-ID'),
            ]);

            $patientWallet = $this->walletRepositories->getOrCreateByOwnerForUpdate($consultation->patient_id);
        }

        // ---------------------------------------------------------------
        // 6. التحقق من كفاية الرصيد
        // ---------------------------------------------------------------
        if ((float)$consultantWallet->pending_balance < $netAmount) {
            Log::channel('financial')->critical('consultation.internal_refund_failed', [
                'reason' => 'insufficient_pending_balance',
                'consultation_id' => $consultation->id,
                'net_amount' => $netAmount,
                'pending_balance' => $consultantWallet->pending_balance,
                'consultant_id' => $consultation->consultant_id,
                'trace_id' => request()->header('X-Trace-ID'),
            ]);
            throw new InsufficientWalletBalanceException($consultation->consultant_id, $netAmount);
        }

        // ---------------------------------------------------------------
        // 7. تسجيل القيود المحاسبية أولاً (Ledger is the source of truth)
        //    القيد المزدوج: Debit المستشار = Credit المريض
        // ---------------------------------------------------------------
        $currency = (string)($consultantWallet->currency ?? 'OMR');
        $meta = ManualRefundMeta::pending($consultation)->toArray();

        $this->createLedgerEntry($consultation, $consultantWallet->id, 'debit', $netAmount, $currency, $meta);
        $this->createLedgerEntry($consultation, $patientWallet->id, 'credit', $netAmount, $currency, $meta);

        // ---------------------------------------------------------------
        // 8. تحديث الأرصدة بعد القيود (atomic SQL — لا fetch-compute-save)
        // ---------------------------------------------------------------
        $consultantWallet->decrement('pending_balance', $netAmount);
        $patientWallet->increment('available_balance', $netAmount);

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
            'amount' => $netAmount,
            'currency' => $currency,
            'patient_id' => $consultation->patient_id,
            'consultant_id' => $consultation->consultant_id,
            'consultant_wallet' => $consultantWallet->id,
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
        $amount = (float)$consultation->net_amount;

        if ($consultation->net_amount === null || $amount <= 0) {
            Log::channel('financial')->critical('consultation.internal_refund_failed', [
                'reason' => 'invalid_net_amount',
                'consultation_id' => $consultation->id,
                'net_amount' => $consultation->net_amount,
                'trace_id' => request()->header('X-Trace-ID'),
            ]);
            throw new InvalidRefundAmountException($consultation->id, $consultation->net_amount);
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

    /**
     * إنشاء قيد واحد في الـ Ledger.
     * يُستدعى مرتين لكل عملية (Debit + Credit) لضمان التوازن المحاسبي.
     */
    private function createLedgerEntry(
        $consultation,
        int $walletId,
        string $entryType,
        float $amount,
        string $currency,
        array $meta,
    ): void
    {
        $this->financialTransactionService->createWalletEntry(
            reference:$consultation,
            gatewayPaymentId: null,
            transactionType: TransactionType::REFUND->value,
            entryType: $entryType,
            walletId: $walletId,
            grossAmount: $amount,
            netAmount: $amount,
            currency: $currency,
            status: AmountStatus::STATUS_AVAILABLE->value,
            meta: $meta,
            platformCommission: 0,
            vatAmount: 0,
        );
    }
}
