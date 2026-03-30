<?php

namespace App\Listeners;

use App\Events\TemporaryPackageAssigned;
use App\Mail\SubscriptionActivatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionEmail
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
    public function handle($event): void
    {
        Mail::to($event->customer->email)
            ->queue(new SubscriptionActivatedMail($event->customer, $event->url));    }
}
