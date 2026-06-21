<?php

namespace App\Console\Commands;

use App\Models\GatewayPayment;
use Illuminate\Console\Command;

class ExpireStalePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:expire-stale';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        GatewayPayment::where('status', 'pending')
//            ->where('created_at', '<', now()->subMinutes(15))
//            ->update(['status' => 'expired']);
//
//        $this->info('Stale payments expired successfully.');
    }
}
