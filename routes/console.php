<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\ProcessBiometricLogs;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ProcessBiometricLogs())
    ->name('process-biometric')
    ->hourly();

Schedule::command('sf:sync-employees')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command('employees:process-terminations')
    ->everyMinute();