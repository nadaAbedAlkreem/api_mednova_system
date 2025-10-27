<?php
namespace App\Services\api;

use App\Events\ConsultationRequested;
use Illuminate\Support\Facades\DB;


class ConsultationStatusService
{
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
//                 $meetingLink = $this->zoomMeetingService->createMeetingLinkZoom( $consultation->appointmentRequest->requested_time ,$consultation->appointmentRequest->confirmed_end_time ,$consultation->patient->email,$consultation->consultant->email);
                   // update  row in consultation   for video_link

                }
                event(new ConsultationRequested($consultation, $message, 'accepted'));
                break;

            case 'cancelled':
                $message = $this->handleCancellation($consultation, $actionBy);
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



}
