<?php

namespace App\Http\Controllers;

use App\Services\AssignUserRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(
        Request $request,
        AssignUserRoleService $roleService
    ) {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'username' => 'Invalid credentials',
            ]);
        }

        $request->session()->regenerate();

        // ✅ AUTO-ASSIGN ROLES HERE
        $roleService->assign(Auth::user());

        return redirect()->intended('/');
    }
}
