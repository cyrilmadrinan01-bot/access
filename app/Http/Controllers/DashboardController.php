<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use App\Models\Leave;
use App\Models\Overtime;
use App\Models\PayrollCutoff;
use App\Models\TimekeepingCorrections;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        /**
         * =====================================================
         * ROLE BASED DASHBOARD
         * =====================================================
         */

        if ($user->hasRole('super-admin')) {
            return $this->index();
        }

        if ($user->hasRole('hr')) {
            return $this->index();
        }

        if ($user->hasRole('payroll')) {
            return $this->index();
        }

        if ($user->hasRole('manager')) {
            return $this->index();
        }

        return $this->index();
    }

    public function index()
    {
        $user = Auth::user();

        /**
         * =====================================================
         * CURRENT CUTOFF
         * =====================================================
         */

        $cutoff = PayrollCutoff::latest()->first();

        $fromDate = $cutoff?->from_date
            ? Carbon::parse($cutoff->from_date)->startOfDay()
            : now()->startOfMonth();

        $toDate = $cutoff?->to_date
            ? Carbon::parse($cutoff->to_date)->endOfDay()
            : now()->endOfMonth();

        /**
         * =====================================================
         * OWN REQUESTS
         * =====================================================
         */

        $filedCorrections = TimekeepingCorrections::where(function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'Pending')
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->where('status', 'Approved')
                           ->whereBetween('date', [$fromDate, $toDate]);
                    });
            })
            ->select('id', 'employee_name', 'date', 'status')
            ->latest()
            ->get();

        $overtimes = Overtime::where(function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'Pending')
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->where('status', 'Approved')
                           ->whereBetween('date', [$fromDate, $toDate]);
                    });
            })
            ->select('id', 'employee_name', 'date', 'status')
            ->latest()
            ->get();

        $timeoffs = Leave::where(function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'Pending')
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->where('status', 'Approved')
                           ->whereBetween('date', [$fromDate, $toDate]);
                    });
            })
            ->select('id', 'employee_name', 'date', 'status')
            ->latest()
            ->get();

        /**
         * =====================================================
         * APPROVALS
         * =====================================================
         */

        $approvalCorrections = TimekeepingCorrections::where('status', 'Pending')
            ->where('approver_id', $user->id)
            ->select('id', 'employee_name', 'date')
            ->latest()
            ->get();

        $approvalOvertimes = Overtime::where('status', 'Pending')
            ->where('approver_id', $user->id)
            ->select('id', 'employee_name', 'date')
            ->latest()
            ->get();

        /**
         * FIXED:
         * Undefined method user() means Leave model has no relation user()
         *
         * Use direct manager filter instead
         * assumes leaves table has managerId column
         */

        $approvalTimeoffs = Leave::where('status', 'Pending')
    ->whereHas('employee', function ($q) use ($user) {
        $q->where('managerId', $user->empnum);
    })
    ->with('employee')
    ->latest()
    ->get();

        /**
         * =====================================================
         * RETURN
         * =====================================================
         */

        return Inertia::render('Dashboard/Employee', [
            'filedCorrections' => $filedCorrections,
            'overtimes' => $overtimes,
            'timeoffs' => $timeoffs,

            'approvalCorrections' => $approvalCorrections,
            'approvalOvertimes' => $approvalOvertimes,
            'approvalTimeoffs' => $approvalTimeoffs,

            'cutoff' => [
                'from' => $fromDate->format('Y-m-d'),
                'to'   => $toDate->format('Y-m-d'),
            ],
        ]);
    }
}
