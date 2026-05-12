<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\ConsultantType;
use App\Enums\FinancialStatus;
use App\Enums\StatusType;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Models\Customer;
use Illuminate\Auth\Access\Response;

class ConsultationPolicy
{
    //Payment is prohibited except for the person eligible for the specific consultation, i.e., the patient requesting the consultation.
    public function pay(Customer $customer, $consultation): Response
    {
         if ($customer->type_account !== ConsultantType::PATIENT->value) {
            return Response::deny(__('policies.consultation.pay.not_patient'));
        }

        if ($consultation->patient_id !== $customer->id) {
            return Response::deny(__('policies.consultation.pay.not_owner'));
        }

        if ($consultation->financial_status !== FinancialStatus::UNPAID->value) {
            return Response::deny(__('policies.consultation.pay.already_paid'));
        }

        return Response::allow();
    }

    public function createRequest(Customer $customer, Customer $consultant): Response
    {
          if ($consultant->approval_status !== StatusType::APPROVED->value) {
            return Response::deny(__('policies.consultation.create_request.not_approved'));
        }

        if ($consultant->account_status !== AccountStatus::ACTIVE->value) {
            return Response::deny(__('policies.consultation.create_request.not_active'));
        }

        return Response::allow();
    }

    public function openDispute(Customer $user, ConsultationChatRequest|ConsultationVideoRequest $consultation): Response {

        if ((int) $consultation->patient_id !== (int) $user->id) {
            return Response::deny(__('policies.consultation.dispute.not_owner'));
        }
        // 2. يجب أن تكون الحالة المالية في review_window
        if ($consultation->financial_status !== FinancialStatus::REVIEW_WINDOW->value) {
            return Response::deny(__('policies.consultation.dispute.not_review_window'));
        }
        // 3. يجب أن تكون المهلة لم تنتهِ
        if (!$consultation->review_deadline || now()->greaterThan($consultation->review_deadline)) {
            return Response::deny(__('policies.consultation.dispute.expired'));
        }

        return Response::allow();
    }
    /**
     * عرض الاستشارة
     */
    public function view(Customer $customer, ConsultationChatRequest|ConsultationVideoRequest $consultation): bool
    {
        return $customer->id === $consultation->patient_id
            || $customer->id === $consultation->consultant_id;
    }

    public function updateStatus(Customer $user, ConsultationChatRequest|ConsultationVideoRequest $consultation): bool
    {
        return (int) $user->id === (int) $consultation->patient_id
            || (int) $user->id === (int) $consultation->consultant_id;
    }

    public function cancelAs(Customer $user, ConsultationChatRequest|ConsultationVideoRequest $consultation, string $actionBy): bool
    {
        return match ($actionBy) {
            'patient'     => (int) $user->id === (int) $consultation->patient_id,
            'consultable' => (int) $user->id === (int) $consultation->consultant_id,
            default       => false,
        };
    }
    public function accept(Customer $user, ConsultationChatRequest|ConsultationVideoRequest $consultation): Response
    {
        if ((int) $user->id !== (int) $consultation->consultant_id) {
            return Response::deny(__('policies.consultation.accept.not_consultant'));
        }

        if ($consultation->financial_status === FinancialStatus::UNPAID->value) {
            return Response::deny(__('policies.consultation.accept.not_paid'));
        }
//        if ($consultation instanceof ConsultationVideoRequest) {
//            $consultation->loadMissing('appointmentRequest');
//
//            if ($consultation->appointmentRequest) {
//                $appointmentTime = \Carbon\Carbon::parse($consultation->appointmentRequest->requested_time);
//                 if ($appointmentTime->isPast()) {
//                    return Response::deny(__('policies.consultation.accept.appointment_passed'));
//                }
//            }
//        }

        return Response::allow();
    }




}
