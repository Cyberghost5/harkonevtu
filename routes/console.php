<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automated Daily Executive Report (Runs every morning at 06:00 AM)
Schedule::command('reports:send-scheduled daily')->dailyAt('06:00');

// Automated Weekly Executive Report (Runs every Monday morning at 07:00 AM)
Schedule::command('reports:send-scheduled weekly')->weeklyOn(1, '07:00');
