<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use App\Listeners\NormalizeUserAfterLogin;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Login::class => [
            NormalizeUserAfterLogin::class,
        ],
        // You can add more events here
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
