<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class AzureAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback()
    {
        $azureUser = Socialite::driver('azure')->user();

        $user = User::firstOrCreate(
            ['email' => $azureUser->email],
            [
                'username' => $azureUser->email,
                'empnum' => $azureUser->user['employeeId'] ?? null,
                'auth_provider' => 'azure',
                'employeeStatus' => 'ACTIVE'
            ]
        );

        Auth::login($user);
        return redirect('/dashboard');
    }
}
