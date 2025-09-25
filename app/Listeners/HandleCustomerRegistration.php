<?php

namespace App\Listeners;

use App\Events\CustomerRegistered;
use App\Jobs\ProcessCustomerRegistrationJob;


class HandleCustomerRegistration
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CustomerRegistered $event): void
    {
        ProcessCustomerRegistrationJob::dispatch($event->customer);
    }
}
