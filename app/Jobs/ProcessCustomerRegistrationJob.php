<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Repositories\IOmnixLogRepositories;
use App\Repositories\IOmnixSubscribeRepositories;
use App\Repositories\IOmnixWebhookRepositories;
use App\Services\Omnix\NotificationManager;
use App\Services\Omnix\OmnixLogService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCustomerRegistrationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $tries = 3;

    public function __construct(protected Customer $customer) {

    }
    /**
     * Execute the job.
     */
    public function handle(IOmnixSubscribeRepositories $omnixSubscribeRepositories,
                           NotificationManager $notificationManager,
                           OmnixLogService $log ,
                           IOmnixWebhookRepositories $webhookRepo
    ): void {
        try {
            if (!$this->customer->omnix_user_id) {
                $omnixUserId = $omnixSubscribeRepositories->subscribeCustomer($this->customer->toArray());
                $this->customer->update(['omnix_user_id' => $omnixUserId]);
            }
            $response = $notificationManager->send('whatsapp' ,$this->customer, "مرحبًا {$this->customer->first_name} في نظامنا 🎉");
            $log->record($this->customer, null , 'user_registered', 'success', $response);
            try {
                $webhooks = $webhookRepo->getInboundWebhooks();
                $log->record($this->customer, null, 'get_inbound_webhooks', 'success', $webhooks);
            } catch (Exception $e) {
                $log->record($this->customer, null ,  'get_inbound_webhooks', 'failed', $e->getMessage());
            }
        } catch (Exception $e) {
            $log->record($this->customer, null , 'user_registered', 'failed', $e->getMessage());
            throw $e;
        }
    }

}
