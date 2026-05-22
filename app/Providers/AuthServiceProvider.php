<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Employee::class => \App\Policies\EmployeePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        /**
         * Super Admin override
         */
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
            return null;
        });

        /**
         * Leave Management
         */
        Gate::define('leave.manage', fn (User $user) =>
            $user->hasPermissionTo('manage leave')
        );

        Gate::define('manage-device', function ($user) {
            return $user->hasRole(['super-admin']);
        });

        /**
         * View Employee Profile
         */
        Gate::define('view-employee-profile', function (User $user, Employee $employee) {

            // HR / Payroll → full access
            if ($user->hasAnyRole(['hr', 'payroll'])) {
                return true;
            }

            // Employee → own profile only
            if ($user->hasRole('employee')) {
                return $user->empnum === $employee->empnum;
            }

            // Manager → direct reports only
            if ($user->hasRole('manager')) {
                return $employee->managerId === $user->empnum;
            }

            return false;
        });
    }
}
