<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'manage medical',
            'manage timekeeping',
            'approve corrections',
            'manage payroll',
            'manage users',
            'manage roles',
            'employee.update.employment',
            'employee.update.personal',
            'employee.update.compensation',
            'employee.update.benefits',
            'employee.update.contact',
            'employee.update.address',
            'employee.update.government',
            'employee.update.emergency',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }

        Role::firstOrCreate(['name' => 'employee'])
            ->givePermissionTo([
                'view dashboard',
                'employee.update.contact',
                'employee.update.government',
                'employee.update.emergency',
                ]);

        Role::firstOrCreate(['name' => 'manager'])
            ->givePermissionTo([
                'view dashboard',
                'approve corrections',
                'approve leave',
                'approve overtime',
                'delegate approver',
            ]);

        Role::firstOrCreate(['name' => 'hr'])
            ->givePermissionTo([
                'view employee profile',
                'manage employees',
                'view hr reports',
                'manage users',
                'manage leave',
                'manage shiftcode',
                'employee.update.employment',
                'employee.update.compensation',
                'employee.update.benefits',
                'employee.update.personal',
                'employee.update.contact',
                'employee.update.address',
                'employee.update.government',
            ]);

        Role::firstOrCreate(['name' => 'payroll'])
            ->givePermissionTo([
                'manage payroll',
                'process payroll',
                'view payroll reports',
                'manage medical',
                ]);

        Role::firstOrCreate(['name' => 'super-admin'])
            ->givePermissionTo(Permission::all());

        Role::firstOrCreate(['name' => 'admin'])
            ->givePermissionTo([
                'view employee profile',
                'manage employees',
                'view hr reports',
                'manage users',
                'manage leave',
                'manage shiftcode',
            ]);
    }
}
