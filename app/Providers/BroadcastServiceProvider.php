<?php

namespace App\Providers;

use App\Events\ConsultationVideoApproval;
use App\Listeners\SendConsultationVideoApprovalApiRequest;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\ConsultationRequested;
use App\Listeners\SendConsultationNotification;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot()
    {
//        Broadcast::routes(['middleware' => ['api', 'auth:sanctum']]);
//        require base_path('routes/channels.php');
    }
}
