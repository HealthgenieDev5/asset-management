<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send asset expiry reminder emails daily at 09:00 IST (03:30 UTC)
Schedule::command('assets:send-reminders')
    ->dailyAt('03:30')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/reminders.log'));
