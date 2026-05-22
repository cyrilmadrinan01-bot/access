<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\SyncTimeOffJob;

class ConsoleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Check if auto-run is enabled
            if (config('successfactors.auto_run')) {
                $schedule->job(new SyncTimeOffJob())
                         ->dailyAt('02:00')  // run every day at 2 AM
                         ->withoutOverlapping();
            }
        });
    }
}
