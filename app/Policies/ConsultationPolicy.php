<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\ConsultantType;
use App\Enums\FinancialStatus;
use App\Enums\StatusType;
use App\Models\Customer;
use Illuminate\Auth\Access\Response;

class ConsultationPolicy
{
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
    /**
     * عرض الاستشارة
     */
    public function view(Customer $customer, $consultation): bool
    {
        return $customer->id === $consultation->patient_id
            || $customer->id === $consultation->consultant_id;
    }
}
