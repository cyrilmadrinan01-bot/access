<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\{Overtime, Timekeeping};
use App\Models\PayrollCutOff;
use Inertia\Inertia;
use Carbon\Carbon;


class OvertimeApprovalController extends Controller
{
    // List overtimes
    public function index()
    {
        return Inertia::render('approvals/Overtimes', [
            'pendingOvertimes' => Overtime::with(['timekeeping.latestCorrection', 'creator'])
                ->where('status', 'Pending')
                ->orderBy('created_at')
                ->get(),

            'approvedOvertimes' => Overtime::with(['timekeeping.latestCorrection', 'creator'])
                ->where('status', 'Approved')
                ->orderBy('created_at','desc')
                ->get(),
            
            'rejectedOvertimes' => Overtime::with(['timekeeping.latestCorrection', 'creator'])
                ->where('status', 'Rejected')
                ->orderBy('created_at','desc')
                ->get(),
        ]);
    }

    private function computeNsdHours(Carbon $start, Carbon $end): float
    {
        // NSD window: 10PM - 6AM
        $nsdStart = $start->copy()->setTime(22, 0);
        $nsdEnd   = $nsdStart->copy()->addHours(8); // until 6AM next day

        // Ensure NSD window covers the OT range
        if ($end < $nsdStart) {
            $nsdStart->subDay();
            $nsdEnd->subDay();
        }

        $overlapStart = max($start, $nsdStart);
        $overlapEnd   = min($end, $nsdEnd);

        if ($overlapEnd <= $overlapStart) {
            return 0;
        }

        return round($overlapStart->diffInMinutes($overlapEnd) / 60, 2);
    }
    public function approve(Overtime $overtime)
    {
        if ($overtime->status !== 'Pending') {
            throw ValidationException::withMessages([
                'overtime' => 'Only pending overtime can be approved.',
            ]);
        }

        $timekeeping = $overtime->timekeeping;
        if (!$timekeeping) {
            throw ValidationException::withMessages([
                'overtime' => 'Timekeeping record not found.',
            ]);
        }

        // Block if timekeeping correction is pending
        $pendingCorrection = $timekeeping->activeCorrections()
            ->where('status', 'Pending')
            ->exists();
 
        if ($pendingCorrection) {
            throw ValidationException::withMessages([
                'overtime' => "Cannot approve overtime. Timekeeping correction is pending.",
            ]);
        }

        $start = Carbon::parse($overtime->startTime);
        $end   = Carbon::parse($overtime->endTime);

        if ($end <= $start) {
            $end->addDay();
        }

        // 🔍 Get current open cutoff
        $currentCutoff = PayrollCutOff::where('current', 'Yes')->first();

        $isAdjustment = $currentCutoff &&
            Carbon::parse($overtime->overtimeDate)->lt(
                Carbon::parse($currentCutoff->cutOffStart)
            );

        // 🔍 Get previous approved OT on same date
        $previousOTs = Overtime::where('empnum', $overtime->empnum)
            ->where('overtimeDate', $overtime->overtimeDate)
            ->where('status', 'Approved')
            ->where('id', '!=', $overtime->id)
            ->get();

        // 🧮 Compute non-overlapping minutes
        $approvedMinutes = $this->computeNonOverlappingMinutes(
            $start,
            $end,
            $previousOTs
        );

        if ($approvedMinutes <= 0) {
            throw ValidationException::withMessages([
                'overtime' => 'This overtime is already fully covered by a previous filing.',
            ]);
        }

        $approvedHours = round($approvedMinutes / 60, 2);
        $meal = round($approvedHours / 3);

        // Recompute NSD only on approved portion
        $approvedEnd = (clone $start)->addMinutes($approvedMinutes);
        $nsdHours = $this->computeNsdHours($start, $approvedEnd);

        $overtime->update([
            'hours'             => $approvedHours,
            'nsd'               => $nsdHours,
            'status'            => 'Approved',
            'is_adjustment'     => $isAdjustment ? 1 : 0,
            'meal_eligible'     => $meal,
            'approved_by'       => Auth::user()->empnum,
            'payroll_cut_off_id'=> $currentCutoff ? $currentCutoff->id : null, // ✅ store cutoff
        ]);

        $this->recomputeTimekeepingOvertime($timekeeping->id);

        return redirect()
            ->route('approvals.overtime')
            ->with(
                'success',
                $isAdjustment
                    ? "Overtime approved as ADJUSTMENT ({$approvedHours} hrs)."
                    : "Overtime approved successfully."
            );
    }

    /**
     * Bulk approval example
     */
    public function bulkApprove(Request $request)
    {
        $overtimeIds = $request->ids;

        $overtimes = Overtime::whereIn('id', $overtimeIds)
            ->where('status', 'Pending')
            ->with('timekeeping')
            ->get();

        $activeCutoff = PayrollCutOff::where('status', 'Active')->first();

        if (!$activeCutoff) {
            return back()->withErrors([
                'payroll' => 'No active payroll cutoff found.',
            ]);
        }

        $approvedCount = 0;
        $skipped = [];

        foreach ($overtimes as $overtime) {

            $timekeeping = $overtime->timekeeping;

            if (!$timekeeping) {
                $skipped[] = "{$overtime->empnum} ({$overtime->overtimeDate}) - No TK";
                continue;
            }

            // ❌ Skip if pending correction exists
            $hasPendingCorrection = $timekeeping->activeCorrections()
                ->where('status', 'Pending')
                ->exists();

            if ($hasPendingCorrection) {
                $skipped[] = "{$overtime->empnum} ({$overtime->overtimeDate}) - Pending correction";
                continue;
            }

            $start = Carbon::parse($overtime->startTime);
            $end   = Carbon::parse($overtime->endTime);

            if ($end <= $start) {
                $end->addDay();
            }

            // 🔎 Detect adjustment
            $isAdjustment = !(
                $overtime->overtimeDate >= $activeCutoff->cutOffStart &&
                $overtime->overtimeDate <= $activeCutoff->cutOffEnd
            );

            // 🔁 Get existing approved OT for overlap deduction
            $existingOTs = Overtime::where('empnum', $overtime->empnum)
                ->where('status', 'Approved')
                ->whereDate('overtimeDate', $overtime->overtimeDate)
                ->get();

            $nonOverlapMinutes = $this->computeNonOverlappingMinutes(
                $start,
                $end,
                $existingOTs
            );

            if ($nonOverlapMinutes <= 0) {
                $skipped[] = "{$overtime->empnum} ({$overtime->overtimeDate}) - Fully overlapped";
                continue;
            }

            $hours = round($nonOverlapMinutes / 60, 2);
            $nsd   = $this->computeNsdHours($start, $end);

            // ✅ APPROVE WITH ADJUSTMENT FLAG AND PAYROLL CUTOFF
            $overtime->update([
                'status'            => 'Approved',
                'hours'             => $hours,
                'nsd'               => $nsd,
                'is_adjustment'     => $isAdjustment,
                'approved_by'       => Auth::user()->empnum,
                'payroll_cut_off_id'=> $activeCutoff ? $activeCutoff->id : null, // 🔹 store cutoff
            ]);

            $this->recomputeTimekeepingOvertime($timekeeping->id);

            $approvedCount++;
        }

        $message = "{$approvedCount} overtime(s) approved successfully.";

        if (!empty($skipped)) {
            $message .= ' Skipped: ' . implode(', ', $skipped);
        }

        return back()->with('success', $message);
    }

    protected function recomputeTimekeepingOvertime($timekeepingId)
    {
        $totals = Overtime::where('timekeeping_id', $timekeepingId)
            ->where('status', 'Approved')
            ->selectRaw('
                COALESCE(SUM(hours), 0) as total_hours,
                COALESCE(SUM(nsd), 0) as total_nsd
            ')
            ->first();

        Timekeeping::where('id', $timekeepingId)->update([
            'overtime' => round($totals->total_hours, 2),
            'overtime_nsd'     => round($totals->total_nsd, 2),
        ]);
    }


    public function cancelApproval(Overtime $overtime)
    {
        // Only approved overtimes can be canceled
        if ($overtime->status !== 'Approved') {
            throw ValidationException::withMessages([
                'overtime' => 'Only approved overtime can be canceled.',
            ]);
        }

        // Revert overtime back to Pending
        $overtime->update([
            'status' => 'Pending',
            'nsd' => null,
            'approved_by' => null,
        ]);

        return redirect()
            ->route('approvals.overtime')
            ->with('success', "Approval for overtime #{$overtime->id} has been canceled.");
    }

    public function reject(Request $request, Overtime $overtime)
    {
        // Only pending overtime can be rejected
        if ($overtime->status !== 'Pending') {
            throw ValidationException::withMessages([
                'overtime' => 'Only pending overtime can be rejected.',
            ]);
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $overtime->update([
            'status' => 'Rejected',
            'reject_reason' => $request->reason ?? 'No reason provided',
            'approved_by' => null, // clear any previous approval just in case
        ]);

        return redirect()
            ->route('approvals.overtime')
            ->with('success', "Overtime #{$overtime->id} has been rejected.");
    }

    public function cancelRejection(Overtime $overtime)
    {
        if ($overtime->status !== 'Rejected') {
            throw ValidationException::withMessages([
                'overtime' => 'Only rejected overtime can be reverted.',
            ]);
        }

        $overtime->update([
            'status' => 'Pending',
            'reject_reason' => null,
            'approved_by' => null,
        ]);

        return redirect()
            ->route('approvals.overtime')
            ->with('success', "Overtime #{$overtime->id} has been reverted to pending.");
    }

    private function computeNonOverlappingMinutes(
        Carbon $start,
        Carbon $end,
        Collection $existingOTs
    ): int {
        $minutes = $start->diffInMinutes($end);

        foreach ($existingOTs as $ot) {
            $otStart = Carbon::parse($ot->startTime);
            $otEnd   = Carbon::parse($ot->endTime);

            if ($otEnd <= $otStart) {
                $otEnd->addDay();
            }

            $overlapStart = max($start, $otStart);
            $overlapEnd   = min($end, $otEnd);

            if ($overlapStart < $overlapEnd) {
                $minutes -= $overlapStart->diffInMinutes($overlapEnd);
            }
        }

        return max(0, $minutes);
    }



}