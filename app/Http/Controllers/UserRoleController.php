<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $users = User::with('roles')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        // Transform paginated collection
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'selectedRoles' => $user->roles->pluck('name')->toArray(),
            ];
        });

        $roles = Role::select('id', 'name')->get();

        return Inertia::render('users/Roles', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'roles' => ['array']
        ]);

        $user->syncRoles($request->roles);

        return back()->with('message', 'Roles updated.');
    }
}