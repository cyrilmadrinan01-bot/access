<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LdapLoginController extends Controller
{
    public function login(Request $request)
    {
        if (!Ldap::auth()->attempt($request->username, $request->password)) {
            throw ValidationException::withMessages(['username' => 'Invalid credentials']);
        }

        $user = User::where('username', $request->username)->firstOrFail();
        Auth::login($user);

        app(RoleAssignmentService::class)->assign($user);
        return redirect()->intended();
    }
}
