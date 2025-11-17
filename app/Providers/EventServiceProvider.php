<?php

namespace App\Providers;

use App\Events\ConsultationVideoApproval;
use App\Events\MessageRead;
use App\Listeners\SendConsultationVideoApprovalApiRequest;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\ConsultationRequested;
use App\Listeners\SendConsultationNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ConsultationRequested::class => [
            SendConsultationNotification::class,
        ],
        ConsultationVideoApproval::class => [
            SendConsultationVideoApprovalApiRequest::class,
        ],
        MessageRead::class => [],


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
