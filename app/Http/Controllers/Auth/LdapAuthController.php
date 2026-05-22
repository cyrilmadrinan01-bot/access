<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;

class LdapAuthController extends Controller
{
    public function login($username, $password)
    {
        $ldapUser = LdapUser::where('samaccountname', $username)->first();

        if (! $ldapUser || ! $ldapUser->authenticate($password)) {
            abort(401);
        }

        $user = User::firstOrCreate(
            ['username' => $username],
            [
                'empnum' => $ldapUser->employeeid[0],
                'auth_provider' => 'ldap',
                'employeeStatus' => 'ACTIVE'
            ]
        );

        Auth::login($user);
        return redirect('/dashboard');
    }
}
