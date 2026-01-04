<?php

namespace App\Models\Scopes;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveVerifiedCustomerScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder
            ->where('status', Customer::STATUS_ACTIVE)
            ->whereNotNull('email_verified_at')
            ->where('is_banned', false)
    }
}
