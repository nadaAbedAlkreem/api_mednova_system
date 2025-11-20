<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('consultations:update-status')->everyFiveSeconds();
Schedule::command('app:update-video-consultation-status')->everyFiveSeconds();
//Schedule::command('app:check-pairing-of-smart-glove')->everyFiveSeconds();
//Schedule::command('app:check-pending-glove-commands')->everyFiveSeconds();
