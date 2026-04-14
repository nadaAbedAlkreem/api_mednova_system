<?php
namespace App\Services\Api\Consultation;

use App\Events\ConsultationRequested;
use App\Events\ConsultationVideoApproval;
use App\Repositories\IWalletRepositories;
use App\Services\Financial\FinancialTransactionService;
use Illuminate\Support\Facades\DB;


class ConsultationStatusService
{
    public function __construct(
        private readonly IWalletRepositories $wallets,
        private readonly FinancialTransactionService $financialTransactions
    ) {}

    protected ZoomMeetingService $zoomMeetingService;

    public function handleStatusChange($consultation, string $status , string $type, ?string $actionBy = null): string
    {
       return DB::Transaction(function () use ($consultation, $status, $type, $actionBy) {
        $consultation->load(['patient', 'consultant']);
        switch ($status) {
            case 'accepted':
                $message = __('messages.ACCEPTED_REQUEST', [
                    'name' => $consultation->consultant->full_name,
                ]);
                if($type == 'video'){
                    $consultation->load('appointmentRequest');
                    if($consultation->appointmentRequest != null){
                        event(new ConsultationVideoApproval($consultation->appointmentRequest->requested_time, 60 ,$consultation));
                        $consultation->appointmentRequest->update(['status' => 'approved']);
                    }
                }
                event(new ConsultationRequested($consultation, $message, 'accepted'));
                break;

            case 'cancelled':
                $message = $this->handleCancellation($consultation, $actionBy);
                if($type == 'video'){
                    $consultation->load('appointmentRequest');
                    if($consultation->appointmentRequest != null){$consultation->appointmentRequest->update(['status' => 'cancelled']);}
                }
                $consultation->delete();
                break;

            default:
                $message = __('messages.STATUS_UPDATED');
                break;
        }
        return $message;
        });
    }

    private function handleCancellation($consultation, ?string $actionBy): string
    {
        return  DB::Transaction(function () use ($consultation, $actionBy) {
            if ($actionBy === 'patient') {
                $message = __('messages.CANCEL_REQUEST_PATIENT', [
                    'name' => $consultation->patient->full_name
                ]);
                event(new ConsultationRequested($consultation, $message, 'cancelled_by_patient'));
                $consultation = $consultation->newQuery()
                    ->whereKey($consultation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($consultation->financial_status !== \App\Enums\FinancialStatus::HELD->value) {
                    \Illuminate\Support\Facades\Log::channel('financial')->info('consultation.internal_refund_skipped', [
                        'consultation_id' => $consultation->id,
                        'financial_status' => $consultation->financial_status,
                        'reason' => 'status_is_not_held',
                    ]);
                    return $message;
                }

                $alreadyRefunded = \App\Models\Transaction::query()
                    ->where('reference_type', get_class($consultation))
                    ->where('reference_id', $consultation->id)
                    ->where('transaction_type', \App\Enums\TransactionType::REFUND->value)
                    ->exists();
                if ($alreadyRefunded) {
                    \Illuminate\Support\Facades\Log::channel('financial')->warning('consultation.internal_refund_skipped', [
                        'consultation_id' => $consultation->id,
                        'reason' => 'refund_transaction_exists',
                    ]);
                    return $message;
                }

                $netAmount = (float) $consultation->net_amount;
                if ($consultation->net_amount === null || $netAmount <= 0) {
                    throw new \RuntimeException('Invalid consultation net amount for internal refund.');
                }

                $consultantWallet = $this->wallets->findByOwnerForUpdate($consultation->consultant_id);
                $patientWallet = $this->wallets->getOrCreateByOwnerForUpdate(
                    $consultation->patient_id,
                    $consultantWallet?->currency ?? 'OMR'
                );

                if (!$consultantWallet) {
                    throw new \RuntimeException('Consultant wallet not found for internal refund operation.');
                }

                if ((float) $consultantWallet->pending_balance < $netAmount) {
                    throw new \RuntimeException('Insufficient consultant pending balance for internal refund.');
                }

                $consultantWallet->pending_balance = (float) $consultantWallet->pending_balance - $netAmount;
                $consultantWallet->save();

                $patientWallet->available_balance = (float) $patientWallet->available_balance + $netAmount;
                $patientWallet->save();

                $this->createInternalRefundTransaction(
                    $consultation,
                    $consultantWallet->id,
                    'debit',
                    $netAmount,
                    (string) ($consultantWallet->currency ?? 'OMR')
                );
                $this->createInternalRefundTransaction(
                    $consultation,
                    $patientWallet->id,
                    'credit',
                    $netAmount,
                    (string) ($patientWallet->currency ?? 'OMR')
                );

                $consultation->update(['financial_status' => \App\Enums\FinancialStatus::REFUNDED_INTERNAL->value]);

                \Illuminate\Support\Facades\Log::channel('financial')->info('consultation.internal_refund_created', [
                    'consultation_id' => $consultation->id,
                    'amount' => $netAmount,
                    'patient_id' => $consultation->patient_id,
                    'consultant_id' => $consultation->consultant_id,
                ]);

            } else if ($actionBy === 'consultable') {
                $message = __('messages.CANCEL_REQUEST_CONSULTANT', [
                    'name' => $consultation->consultant->full_name
                ]);
                event(new ConsultationRequested($consultation, $message, 'cancelled_by_consultant'));

            } else {
                $message = __('messages.CANCEL_REQUEST');
            }

            return $message;
        });

    }

    private function createInternalRefundTransaction($consultation, int $walletId, string $entryType, float $netAmount, string $currency): void
    {
        $this->financialTransactions->createWalletEntry(
            referenceType: get_class($consultation),
            referenceId: $consultation->id,
            gatewayPaymentId: null,
            transactionType: \App\Enums\TransactionType::REFUND->value,
            entryType: $entryType,
            walletId: $walletId,
            grossAmount: $netAmount,
            netAmount: $netAmount,
            currency: $currency,
            status: 'available',
            meta: [
                'manual_refund_required' => true,
                'manual_refund_completed' => false,
                'consultation_id' => $consultation->id,
                'patient_id' => $consultation->patient_id,
                'consultant_id' => $consultation->consultant_id,
            ],
            platformCommission: 0,
            vatAmount: 0,
        );
    }



}
