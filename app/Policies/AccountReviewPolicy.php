<?php

namespace App\Policies;

use App\Models\AccountReview;
use App\Models\Customer;
use Illuminate\Auth\Access\Response;

class AccountReviewPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Customer $customer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Customer $customer, AccountReview $accountReview): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Customer $customer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Customer $customer, AccountReview $accountReview): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Customer $customer, AccountReview $accountReview): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Customer $customer, AccountReview $accountReview): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Customer $customer, AccountReview $accountReview): bool
    {
        return false;
    }
}
