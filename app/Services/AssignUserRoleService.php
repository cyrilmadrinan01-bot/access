<?php

namespace App\Services;

use App\Models\User;

class AssignUserRoleService
{
    public function assign(User $user): void
    {
        // Do not touch system roles
        if ($user->hasAnyRole(['hr','payroll','admin','super-admin'])) return;

        $employee = $user->employee;
        if (! $employee) return;

        $user->syncRoles(['employee']); // default role

        if ((int)$employee->payGrade >= 43 || $employee->subordinates()->exists()) {
            $user->assignRole('manager');
        }
    }

}


