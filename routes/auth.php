<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticatedSessionController;
use Inertia\Inertia;

// Show login page
Route::get('/login', function () {
    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'canRegister' => Route::has('register'),
    ]);
})->name('login');

// Handle login form submission
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.post');
