<?php
namespace App\Services\Api\Consultation;

use App\Events\ConsultationRequested;
use App\Events\ConsultationVideoApproval;
use App\Exceptions\ConsultantWalletNotFoundException;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Exceptions\InvalidRefundAmountException;
use App\Repositories\IWalletRepositories;
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
                event(new ConsultationRequested($consultation, __('messages.ACCEPTED_REQUEST' ,['name' => $consultation->consultant->full_name]), 'requested'));
                $message = __('messages.STATUS_UPDATED');
                break;

            case 'cancelled':
                $message = $this->processCancellation($consultation, $actionBy);
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
        return match ($actionBy) {
            'patient'     => $this->handleCancellation($consultation, 'patient'),
            'consultant' => $this->handleCancellation($consultation, 'consultant'),
            default       => __('messages.CANCEL_REQUEST'),
        };
    }


    /**
     * @throws InvalidRefundAmountException
     */
    private function handleCancellation($consultation, string $type): string
    {
        $consultation = $consultation->newQuery()->whereKey($consultation->id)->lockForUpdate()->firstOrFail();
        $actor = $type === 'patient' ? $consultation->patient : $consultation->consultant;
        $messageKey = $type === 'patient' ? 'messages.CANCEL_REQUEST_PATIENT' : 'messages.CANCEL_REQUEST_CONSULTANT';
        $eventType = $type === 'patient' ? 'cancelled_by_patient' : 'cancelled_by_consultant';
        $message = __($messageKey, ['name' => $actor->full_name]);
        $this->refundService->processInternalRefund($consultation);
        event(new ConsultationRequested($consultation, $message, $eventType));

        return $message;
    }


}
