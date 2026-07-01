<?php
namespace App\Services\Api\Consultation;

use App\Events\ConsultationRequested;
use App\Events\ConsultationVideoApproval;
use App\Exceptions\InvalidRefundAmountException;
use App\Services\Api\Financial\ConsultationRefundService;
use Illuminate\Support\Facades\DB;


class ConsultationStatusService
{
    protected ZoomMeetingService $zoomMeetingService;
    public function __construct(
        private readonly ConsultationRefundService $refundService,
    ) {}
    public function handleStatusChange($consultation, string $status , string $type, ?string $actionBy = null): string
    {
       return DB::Transaction(function () use ($consultation, $status, $type, $actionBy) {
        $consultation->load(['patient', 'consultant']);
        switch ($status) {
            case 'accepted':
                if($type == 'video'){
                    $consultation->load('appointmentRequest');
                    if($consultation->appointmentRequest != null){
                        event(new ConsultationVideoApproval($consultation->appointmentRequest->requested_time, 60 ,$consultation));
                        $consultation->appointmentRequest->update(['status' => 'approved']);}}
                event(new ConsultationRequested($consultation, __('messages.ACCEPTED_REQUEST' ,['name' => $consultation->consultant->full_name]), 'accepted'));
                $message = __('messages.STATUS_UPDATED');
                break;

            case 'cancelled':
                $this->processCancellation($consultation, $actionBy);
                if($type == 'video'){
                    $consultation->load('appointmentRequest');
                    if($consultation->appointmentRequest != null){$consultation->appointmentRequest->update(['status' => 'cancelled']);}
                }
//                $consultation->delete();
                $message = __('messages.STATUS_UPDATED');
                break;

            default:
                $message = __('messages.STATUS_UPDATED');
                break;
        }
        return $message;
        });
    }

//    private function handleCancellation($consultation, ?string $actionBy): string
//    {
//        return  DB::Transaction(function () use ($consultation, $actionBy) {
//            if ($actionBy === 'patient') {
//                $message = __('messages.CANCEL_REQUEST_PATIENT', [
//                    'name' => $consultation->patient->full_name
//                ]);
//                event(new ConsultationRequested($consultation, $message, 'cancelled_by_patient'));
//
//            } else if ($actionBy === 'consultable') {
//                $message = __('messages.CANCEL_REQUEST_CONSULTANT', [
//                    'name' => $consultation->consultant->full_name
//                ]);
//                event(new ConsultationRequested($consultation, $message, 'cancelled_by_consultant'));
//
//            } else {
//                $message = __('messages.CANCEL_REQUEST');
//            }
//
//            return $message;
//        });
//
//    }

    /**
     * @throws InvalidRefundAmountException
     */

    private function processCancellation($consultation, ?string $actionBy): string
    {
        \Illuminate\Support\Facades\Log::channel('financial')->info('$actionBy' . $actionBy);
        return match ($actionBy) {
            'patient'     => $this->handleCancellation($consultation, 'patient'),
            'consultable' => $this->handleCancellation($consultation, 'consultant'),
            default => throw new \InvalidArgumentException("Unknown actionBy: {$actionBy}"),
        };
    }


    /**
     * @throws InvalidRefundAmountException
     */
    private function handleCancellation($consultation, string $type): string
    {
        $consultation = $consultation->newQuery()
            ->whereKey($consultation->id)
            ->lockForUpdate()
            ->firstOrFail();

        $actor = $type === 'patient' ? $consultation->patient : $consultation->consultant;

        $messageKey = $type === 'patient' ? 'messages.CANCEL_REQUEST_PATIENT' : 'messages.CANCEL_REQUEST_CONSULTANT';
        $eventType  = $type === 'patient' ? 'cancelled_by_patient' : 'cancelled_by_consultant';
        $message = __($messageKey, ['name' => $actor->full_name]);

        // ── هل الاستشارة مدفوعة؟ ─────────────────────────────
        $fsValue = $consultation->financial_status instanceof \BackedEnum ? $consultation->financial_status->value : (string) $consultation->financial_status;
        $isPaid = in_array($fsValue, [
            \App\Enums\FinancialStatus::HELD->value,
            \App\Enums\FinancialStatus::FROZEN->value,
        ]);

        // ── استرداد فقط إذا كانت مدفوعة ─────────────────────
        if ($isPaid) {
            $this->refundService->processInternalRefund($consultation);
            $patientMessage = __('messages.refund_issued', [
                'amount'   => number_format($consultation->consultation_price, 3),
                'currency' => config('amwal.currency_en') ?? 'OMR',
            ]);
            event(new ConsultationRequested($consultation, $patientMessage, 'refund_issued'));
        }
        // ── إشعار الإلغاء دائماً ────────────────────────────
        if($eventType === 'cancelled_by_consultant')
        {
            event(new ConsultationRequested($consultation, $message, $eventType));
        }

        return $message;
    }


}
