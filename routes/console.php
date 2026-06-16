<?php

use App\Jobs\ReleaseSuspendedConsultationsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('consultations:update-status')->everyMinute()->withoutOverlapping();
Schedule::command('app:update-video-consultation-status')->everyFiveSeconds();
//Schedule::command('app:check-pairing-of-smart-glove')->everyFiveSeconds();
//Schedule::command('app:check-pending-glove-commands')->everyFiveSeconds();
//Schedule::command('consultations:check-status')->hourly(); in production
Schedule::command('consultations:check-status')->everyFifteenSeconds();
Schedule::command('payments:expire-stale')->everyFifteenMinutes();
Schedule::job(new ReleaseSuspendedConsultationsJob)->everyFiveMinutes();
Schedule::command('consultations:process-review-expiry')
         ->everyTenMinutes()
         ->withoutOverlapping()
         ->runInBackground();

Schedule::command('financial:audit')
         ->dailyAt('03:00')
         ->runInBackground();

Schedule::command('monitor:failed-jobs')
         ->dailyAt('03:15')
         ->runInBackground();

