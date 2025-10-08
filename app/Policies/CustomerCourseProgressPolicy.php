<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\CustomerCourseProgress;
use Illuminate\Auth\Access\Response;

class CustomerCourseProgressPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Admin $admin): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Admin $admin, CustomerCourseProgress $customerCourseProgress): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Admin $admin): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Admin $admin, CustomerCourseProgress $customerCourseProgress): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Admin $admin, CustomerCourseProgress $customerCourseProgress): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Admin $admin, CustomerCourseProgress $customerCourseProgress): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Admin $admin, CustomerCourseProgress $customerCourseProgress): bool
    {
        return false;
    }
}
