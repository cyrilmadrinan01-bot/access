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
        return $this->index();
    }

    public function index()
    {
        $user = Auth::user();

        /**
         * =====================================================
         * CURRENT PAYROLL CUTOFF
         * =====================================================
         */

        $currentCutoff = PayrollCutoff::where('current', 'Yes')->first();

        $currentCutoffId = $currentCutoff?->id;
//dd($currentCutoffId);
        /**
         * =====================================================
         * MY REQUESTS
         *
         * RULES:
         * - Pending = always show
         * - Approved/Rejected = current cutoff only
         * =====================================================
         */

        $filedCorrections = TimekeepingCorrections::query()
            ->where('created_by', $user->id)
            ->where(function ($q) use ($currentCutoffId) {

                $q->where('status', 'Pending');

                $q->orWhere(function ($qq) use ($currentCutoffId) {
                    $qq->whereIn('status', ['Approved', 'Rejected'])
                        ->where('payroll_cut_off_id', $currentCutoffId);
                });
            })
            ->select([
                'id',
                'status',
                'created_at',
                'payroll_cut_off_id',
                'rejected_reason',
            ])
            ->latest()
            ->get();


        $overtimes = Overtime::query()
            ->where(function ($q) use ($currentCutoffId) {

                $q->where('status', 'Pending');

                $q->orWhere(function ($qq) use ($currentCutoffId) {
                    $qq->whereIn('status', ['Approved', 'Rejected'])
                        ->where('payroll_cut_off_id', $currentCutoffId);
                });
            })
            ->where('empnum', $user->empnum)
            ->select([
                'id',
                'status',
                'created_at',
                'payroll_cut_off_id',
            ])
            ->latest()
            ->get();

        $timeoffs = Leave::query()
            ->where(function ($q) use ($currentCutoffId) {

                $q->where('status', 'Pending');

                $q->orWhere(function ($qq) use ($currentCutoffId) {
                    $qq->whereIn('status', ['Approved', 'Rejected']);
                });
            })
            ->where('empnum', $user->empnum)
            ->select([
                'id',
                'status',
                'created_at',
            ])
            ->latest()
            ->get();

        /**
         * =====================================================
         * PENDING APPROVALS
         * =====================================================
         */

        $approvalCorrections = TimekeepingCorrections::where('status', 'Pending')
            ->whereHas('creator.employee', function ($q) use ($user) {
                $q->where('manager_empnum', $user->empnum);
            })
            ->with('creator.employee')
            ->latest()
            ->get();


        $approvalOvertimes = Overtime::where('status', 'Pending')
            ->whereHas('employee', function ($q) use ($user) {
                $q->where('manager_empnum', $user->empnum);
            })
            ->with('employee')
            ->latest()
            ->get();


        $approvalTimeoffs = Leave::where('status', 'Pending')
            ->whereHas('employee', function ($q) use ($user) {
                $q->where('manager_empnum', $user->empnum);
            })
            ->with('employee')
            ->latest()
            ->get();

        return Inertia::render('Dashboard', [
            'filedCorrections' => $filedCorrections,
            'overtimes' => $overtimes,
            'timeoffs' => $timeoffs,

            'approvalCorrections' => $approvalCorrections,
            'approvalOvertimes' => $approvalOvertimes,
            'approvalTimeoffs' => $approvalTimeoffs,

            'cutoff' => $currentCutoff,
        ]);
        
    }
}