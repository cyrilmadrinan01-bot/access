<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Foundation\Inspiring;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\TimekeepingCorrections;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();

        $pendingCorrectionCount = 0;
        $pendingOvertimeCount   = 0;
        $pendingLeaveCount      = 0;

        if ($user) {
            // Pending timekeeping corrections
            $pendingCorrectionCount = TimekeepingCorrections::where('status', 'Pending')
                ->whereHas('creator.employee', function ($q) use ($user) {
                    $q->where('managerId', $user->empnum);
                })
                ->count();

            // Pending overtimes
            $pendingOvertimeCount = Overtime::where('status', 'Pending')
                ->whereHas('employee', function ($q) use ($user) {
                    $q->where('managerId', $user->empnum);
                })
                ->count();

            // ✅ Pending leaves (SOURCE OF TRUTH = leaves table)
            $pendingLeaveCount = Leave::where('status', Leave::STATUS_PENDING)
            ->whereHas('employee', function ($q) use ($user) {
                $q->where('managerId', $user->empnum);
            })
            ->count();

        }

        return [
            ...parent::share($request),

            'name' => config('app.name'),

            'quote' => [
                'message' => trim($message),
                'author'  => trim($author),
            ],

            'auth' => [
                'user' => $user ? [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'empnum'      => $user->empnum,
                    'roles'       => method_exists($user, 'getRoleNames')
                        ? $user->getRoleNames()
                        : [],
                    'permissions' => method_exists($user, 'getAllPermissions')
                        ? $user->getAllPermissions()->pluck('name')->toArray()
                        : [],
                ] : null,
            ],

            // 🔔 Sidebar notification counters
            'pendingCorrectionCount' => $pendingCorrectionCount,
            'pendingOvertimeCount'   => $pendingOvertimeCount,
            'pendingLeaveCount'      => $pendingLeaveCount,

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',

            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
