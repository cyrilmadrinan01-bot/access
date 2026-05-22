<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CanViewEmployeeProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->route('employee');

        if (! $employee || Gate::denies('view-employee-profile', $employee)) {
            //abort(403, 'You are not allowed to view this profile.');
        }

        return $next($request);
    }
}
