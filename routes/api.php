<?php

use App\Http\Controllers\BiometricController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::prefix('biometric')->group(function () {
    Route::get('/{empnum}/{ip}', [BiometricController::class, 'show']);
    Route::get('/validate/{empnum}/{ip}', [BiometricController::class, 'validate']);
    Route::post('/scan', [BiometricController::class, 'store']);
});

