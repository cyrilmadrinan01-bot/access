<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $roles = Role::with('permissions')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        $roles->getCollection()->transform(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'selectedPermissions' => $role->permissions
                    ->pluck('name')
                    ->toArray(),
            ];
        });

        $permissions = Permission::select(
            'id',
            'name'
        )->orderBy('name')->get();

        return Inertia::render('users/Permission', [
            'roles' => $roles,
            'permissions' => $permissions,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => ['array'],
        ]);

        $role->syncPermissions(
            $request->permissions ?? []
        );

        return back()->with(
            'message',
            'Permissions updated successfully.'
        );
    }
}
