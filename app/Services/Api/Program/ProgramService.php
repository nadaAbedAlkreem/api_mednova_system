<?php
namespace App\Services\Api\Program;

use App\Enums\AccountStatus;
use App\Enums\StatusType;
use App\Events\CustomerApprovalStatusChanged;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\Customer;
use App\Models\Program;
use App\Repositories\IAccountReviewRepositories;
use App\Repositories\ICustomerRepositories;
use App\Repositories\IProgramRepositories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ProgramService
{

    protected Program $model;
    protected IProgramRepositories $programRepositories;

    public function __construct(IProgramRepositories $programRepositories ,Program $program)
    {
        $this->programRepositories = $programRepositories;
        $this->model = $program;

    }

    public function getAll(array $filters = [], int $limit = 10)
    {
        $query = $this->model->query()->with('creator');
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!isset($filters['is_approved'])) {
            $query->whereNotNull('is_approved');
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('title_ar', 'LIKE', "%{$search}%")
                    ->orWhere('description_ar', 'LIKE', "%{$search}%");
            });
        }
        return $query->paginate($limit);
    }


    public function updateApprovalStatus(Customer $customer,StatusType $status , string $reason = ''): Customer
    {
         $currentAdmin = Auth('admin')->user();
        return DB::transaction(function () use ($customer, $status, $reason, $currentAdmin) {
             if ($customer->approval_status == $status->value) {
                return $customer;
            }

            if (($customer->approval_status ==StatusType::APPROVED->value) && ($status == StatusType::REJECTED)) {
                throw ValidationException::withMessages([
                    'approval_status' => __('messages.CANNOT_REJECT_APPROVED_ACCOUNT')
                ]);
            }

            if ($status === StatusType::APPROVED) {
                 if ($customer->is_banned) {
                    throw ValidationException::withMessages([
                        'approval_status' => __('messages.CANNOT_APPROVE_BLOCKED')
                    ]);
                }

                if (!$customer->email_verified_at) {
                    throw ValidationException::withMessages([
                        'approval_status' => __('messages.EMAIL_NOT_VERIFIED')
                    ]);
                }

                if (!$customer->isProfileCompleted()) {
                    throw ValidationException::withMessages([
                        'approval_status' => __('messages.PROFILE_NOT_COMPLETED')
                    ]);
                }
            }

            $customer->approval_status = $status;
            $customer->save();

            event(new CustomerApprovalStatusChanged(
                $customer,
                $status,
                $reason,
                $currentAdmin->id
            ));

            return $customer;
        });

    }


    public function updateAccountStatus(Customer $customer,AccountStatus $status , string $reason = ''): Customer
    {
        return DB::transaction(function () use ($customer, $status, $reason) {
            if ($customer->approval_status == $status->value) {
                return $customer;
            }
            $customer->account_status = $status;
            $customer->reason = $reason;
            $customer->save();
            return $customer;
        });

    }



}
