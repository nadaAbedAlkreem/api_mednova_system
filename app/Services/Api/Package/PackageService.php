<?php
namespace App\Services\Api\Package;

use App\Enums\AccountStatus;
use App\Enums\StatusType;
use App\Events\CustomerApprovalStatusChanged;
use App\Events\TemporaryPackageAssigned;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\Customer;
use App\Repositories\IAccountReviewRepositories;
use App\Repositories\ICustomerRepositories;
use App\Repositories\IUserPackageRepositories;
use App\Services\Api\Customer\CustomerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PackageService
{
    protected ICustomerRepositories $customerRepositories;
    protected IUserPackageRepositories $userPackageRepositories;
    protected CustomerService $customerService;


    public function __construct(ICustomerRepositories $customerRepositories, CustomerService $customerService , IUserPackageRepositories $userPackageRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->customerService = $customerService;
        $this->userPackageRepositories = $userPackageRepositories;
    }

    /**
     * @throws \Exception
     */
    public function assignTemporaryPackage(int $userId)
    {
        $customer = $this->customerRepositories->findOrFail($userId);

        $this->ensureCustomerIsEligible($customer);
        $this->ensureNoActiveSubscription($userId);

        $userPackage = $this->createTemporaryPackage($userId);

        $userPackage->customer->update([
            'account_status' => AccountStatus::ACTIVE->value,
        ]);

        $this->dispatchPackageAssignedEvent($customer);

        return $userPackage;
    }

    // ─────────────────────────────────────────
    //  Private Helpers
    // ─────────────────────────────────────────

    private function ensureCustomerIsEligible(mixed $customer): void
    {
        $isEligible = $customer->isProfileCompleted()
            && $customer->email_verified_at
            && $customer->approval_status === StatusType::APPROVED->value;

        if (!$isEligible) {
            throw new \Exception(__('messages.Subscription_Restrictions'));
        }
    }

    private function ensureNoActiveSubscription(int $userId): void
    {
        $exists = $this->userPackageRepositories
            ->getWhere(['is_active' => 1, 'customer_id' => $userId])
            ->isNotEmpty();

        if ($exists) {
            throw new \Exception(__('messages.Pre_Subscription_Restrictions'));
        }
    }

    private function createTemporaryPackage(int $userId): mixed
    {
        $now = Carbon::now();

        return $this->userPackageRepositories->create([
            'customer_id' => $userId,
            'package_id'  => 1,
            'starts_at'   => $now,
            'ends_at'     => $now->copy()->addMonth(),
            'is_active'   => 1,
        ]);
    }

    private function dispatchPackageAssignedEvent(mixed $customer): void
    {
        $url = url('https://mednovacare.com/profile');
        event(new TemporaryPackageAssigned($customer, $url));
    }
}
