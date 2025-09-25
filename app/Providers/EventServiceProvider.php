<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    protected $listen = [
        \App\Events\CustomerRegistered::class => [\App\Listeners\HandleCustomerRegistration::class,],
    ];


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

    }
}
