<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\ConsultationVideoRescheduleRequest;
use Illuminate\Auth\Access\Response;

class ConsultationVideoRescheduleRequestPolicy
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
    public function view(Admin $admin, ConsultationVideoRescheduleRequest $consultationVideoRescheduleRequest): bool
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
    public function update(Admin $admin, ConsultationVideoRescheduleRequest $consultationVideoRescheduleRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Admin $admin, ConsultationVideoRescheduleRequest $consultationVideoRescheduleRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Admin $admin, ConsultationVideoRescheduleRequest $consultationVideoRescheduleRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Admin $admin, ConsultationVideoRescheduleRequest $consultationVideoRescheduleRequest): bool
    {
        return false;
    }
}
