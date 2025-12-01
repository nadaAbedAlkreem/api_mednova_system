<?php

namespace App\Console\Commands;

use App\Services\api\VideoConsultationStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateVideoConsultationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-video-consultation-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-manage video consultation statuses.';

    public function handle(VideoConsultationStatusService $service)
    {
        Log::info('7777: ');
        $now = Carbon::now();
        $service->processPending($now);
        $service->processAccepted($now);
        $service->processActive($now);
        $this->info('Video consultation statuses processed successfully.');
    }
}
