<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Services\AssignUserRoleService;

class NormalizeUserAfterLogin
{
    protected AssignUserRoleService $roleService;

    /**
     * Inject the role assignment service
     */
    public function __construct(AssignUserRoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Handle the login event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Ensure the user has an associated employee record
        $employee = $user->employee;
        if (! $employee) {
            return;
        }

        // Delegate to AssignUserRoleService for consistent role logic
        $this->roleService->assign($user);
    }
}
