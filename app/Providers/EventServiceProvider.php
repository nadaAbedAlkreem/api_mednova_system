<?php

namespace App\Providers;

use App\Events\ConsultationVideoApproval;
use App\Events\CustomerApprovalStatusChanged;
use App\Events\MessageRead;
use App\Events\TemporaryPackageAssigned;
use App\Events\WithdrawalStatusChanged;
use App\Listeners\SendApprovalStatusMail;
use App\Listeners\SendApprovalStatusNotification;
use App\Listeners\SendConsultationVideoApprovalApiRequest;
use App\Listeners\SendSubscriptionEmail;
use App\Listeners\SendWithdrawalNotificationListener;
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

        TemporaryPackageAssigned::class => [
            SendSubscriptionEmail::class,
        ],
        MessageRead::class => [],

        WithdrawalStatusChanged::class => [
            SendWithdrawalNotificationListener::class,
        ],
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
