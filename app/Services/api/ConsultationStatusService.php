<?php
namespace App\Services\api;

use App\Events\ConsultationRequested;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\ICustomerRepositories;
use Exception;

class ConsultationStatusService
{

    public function handleStatusChange(ConsultationChatRequest $consultation, string $status, ?string $actionBy = null): string
    {
        $consultation->load(['patient', 'consultant']);

        switch ($status) {
            case 'accepted':
                $message = __('messages.ACCEPTED_REQUEST', [
                    'name' => $consultation->consultant->full_name,
                ]);
                event(new ConsultationRequested($consultation, $message, 'accepted'));
                break;

            case 'cancelled':
                $message = $this->handleCancellation($consultation, $actionBy);
                break;

            default:
                $message = __('messages.STATUS_UPDATED');
                break;
        }

        return $message;
    }

    private function handleCancellation(ConsultationChatRequest $consultation, ?string $actionBy): string
    {
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
    }



}
