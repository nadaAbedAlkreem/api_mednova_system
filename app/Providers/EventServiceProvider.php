<?php

namespace App\Providers;

use App\Events\ConsultationVideoApproval;
use App\Events\CustomerApprovalStatusChanged;
use App\Events\MessageRead;
use App\Listeners\SendApprovalStatusMail;
use App\Listeners\SendApprovalStatusNotification;
use App\Listeners\SendConsultationVideoApprovalApiRequest;
use App\Listeners\StoreApprovalStatusLog;
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
//        CustomerApprovalStatusChanged::class => [
//            SendApprovalStatusMail::class,
//            SendApprovalStatusNotification::class,
//            StoreApprovalStatusLog::class,
//        ],

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
