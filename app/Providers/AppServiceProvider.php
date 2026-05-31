<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    { 
        DB::statement("SET search_path TO access");
        Inertia::share([
            
            // Other shared data
            'name' => 'My App',
            'quote' => 'Welcome!',
            'sidebarOpen' => true,
            'flash' => fn () => session()->has('success') ? ['success' => session('success')] : null,
        ]);
    }
}
