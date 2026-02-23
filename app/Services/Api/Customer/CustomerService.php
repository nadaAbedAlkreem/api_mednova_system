<?php
namespace App\Services\Api\Customer;

use App\Enums\AccountStatus;
use App\Enums\StatusType;
use App\Events\CustomerApprovalStatusChanged;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\Customer;
use App\Repositories\IAccountReviewRepositories;
use App\Repositories\ICustomerRepositories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CustomerService
{

    protected Customer $model;
    protected ICustomerRepositories $customerRepository;
    protected IAccountReviewRepositories $accountReviewRepositories;

    public function __construct(ICustomerRepositories $customerRepository  , IAccountReviewRepositories $accountReviewRepositories, Customer $customer)
    {
        $this->customerRepository = $customerRepository;
        $this->model = $customer;
        $this->accountReviewRepositories = $accountReviewRepositories;

    }

    public function register($data): array
    {
        try {
            $customer = $this->customerRepository->create($data);
            $customerToken =  $customer->createToken('API Token')->plainTextToken;
            return [
                'access_token' =>  'Bearer '.$customerToken ,
                'customer' => $customer
            ] ;
        } catch (\Exception $e) {
             throw new \Exception($e->getMessage());
        }
    }

    public function getAll(array $filters = [], int $limit = 10)
    {
        $query = $this->model->query();
        $query->where('account_status', AccountStatus::ACTIVE);
        if (!empty($filters['type_account'])) {
            $query->where('type_account', $filters['type_account']);
        }
        if (!empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }
        if (isset($filters['verified'])) {
            $query->whereNull(
                'email_verified_at',
                !$filters['verified']
            );
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
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
