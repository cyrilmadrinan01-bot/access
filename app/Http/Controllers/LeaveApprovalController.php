<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Audit;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\Employee;
use App\Models\ManagerDelegation;
use Inertia\Inertia;

class LeaveApprovalController extends Controller
{
    /**
     * Check if current user can approve this employee's leave
     */
    protected function canApprove(Employee $employee): bool
    {
        $user = auth()->user();

        // Direct manager (employees.managerId stores empnum)
        if ($employee->managerId === $user->empnum) {
            return true;
        }

        // Delegated manager
        return ManagerDelegation::where('delegate_id', $user->id)
            ->whereDate('valid_until', '>=', now())
            ->exists();
    }

    /**
     * Pending leaves list
     */
    public function index()
    {
        $user = auth()->user();

        $leaves = Leave::with(['user', 'leaveType'])
            ->whereHas('user.employee', function ($q) use ($user) {
                $q->where('managerId', $user->empnum);
            })
            ->orderByDesc('created_at')
            ->get();
 
        // Separate by status (optional, could also filter in Vue)
        return Inertia::render('approvals/TimeOff', [
            'leaves' => $leaves,
        ]);
    }

    /**
     * Approve leave
     */
    public function approve(Request $request, Leave $leave)
    {
        if ($leave->status !== 'Pending') {
            return back()->with('error', 'This leave request is already processed.'.$leave->status);
        }

        LeaveApproval::create([
            'leave_id'   => $leave->id,
            'approver_id'=> auth()->id(),
            'status'     => 'approved',
            'remarks'    => $request->remarks,
        ]);

        $leave->update([
            'status'      => Leave::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $leave->applyToTimekeeping();

        //audit($leave, 'approved');

        return back()->with('success', 'Leave approved successfully.');
    }

    /**
     * Reject leave
     */
    public function reject(Request $request, Leave $leave)
    {
        $request->validate([
            'remarks' => 'required|string|max:255',
        ]);

        if ($leave->status !== Leave::STATUS_PENDING) {
            return back()->with('error', 'This leave request is already processed.');
        }

        LeaveApproval::create([
            'leave_id'    => $leave->id,
            'approver_id' => auth()->id(),
            'status'      => 'rejected',
            'remarks'     => $request->remarks,
        ]);

        $leave->update([
            'status'  => Leave::STATUS_REJECTED,
            'remarks' => $request->remarks,
        ]);

        $leave->removeFromTimekeeping();

        //audit($leave, 'rejected');

        return back()->with('success', 'Leave rejected successfully.');
    }

    public function cancelApproval(Leave $leave)
    {
        if ($leave->status !== 'Approved') {
            return back()->with('error', 'Leave is not approved.');
        }

        $leave->removeFromTimekeeping();

        $leave->update([
            'status' => Leave::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        LeaveApproval::where('leave_id', $leave->id)->where('status', 'approved')->delete();

        return back()->with('success', 'Approval canceled.');
    }

    public function cancelRejection(Leave $leave)
    {
        if ($leave->status !== 'Rejected') {
            return back()->with('error', 'Leave is not rejected.');
        }

        $leave->update([
            'status' => Leave::STATUS_PENDING,
            'remarks' => null,
        ]);

        LeaveApproval::where('leave_id', $leave->id)->where('status', 'rejected')->delete();

        return back()->with('success', 'Rejection canceled.');
    }

}
