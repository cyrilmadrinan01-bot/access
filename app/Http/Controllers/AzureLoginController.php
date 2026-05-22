<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AzureLoginController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback()
    {
        $azureUser = Socialite::driver('azure')->user();
        $user = User::where('email', $azureUser->email)->firstOrFail();

        Auth::login($user);
        app(RoleAssignmentService::class)->assign($user);

        return redirect('/');
    }
}
