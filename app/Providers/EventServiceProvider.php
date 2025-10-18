<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\ConsultationRequested;
use App\Listeners\SendConsultationNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ConsultationRequested::class => [
            SendConsultationNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
