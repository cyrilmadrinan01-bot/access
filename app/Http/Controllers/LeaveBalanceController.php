<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Services\LeaveAccrualService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with('leaveBalances.leaveType')
            ->paginate(10) // 👈 change per page if needed
            ->withQueryString();

        // Transform paginated collection
        $employees->getCollection()->transform(function ($emp) {

            $leaveBalances = $emp->leaveBalances
                ->mapWithKeys(function ($balance) {
                    return [
                        $balance->leave_type_id => [
                            'leave_type_id' => $balance->leave_type_id,
                            'balance' => (float) $balance->balance,
                        ],
                    ];
                })
                ->toArray();

            return [
                'id' => $emp->id,
                'empnum' => $emp->empnum,
                'name' => $emp->name,
                'leave_balances' => $leaveBalances,
            ];
        });

        return Inertia::render('admin/leave/LeaveBalance', [
            'employees' => $employees,
            'leaveTypes' => LeaveType::active()->get(['id', 'code', 'name']),
        ]);
    }


    public function adjust(Request $request)
    {
        $user = Auth::user();

        LeaveAccrualService::accrue(
            $request->empnum,
            $request->leave_type_id,
            $request->amount,
            'Adjustment',
            $request->remarks,
            //auth()->id()
            $user->id
        );

        return back();
    }

    public function massAccrual(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx'
        ]);

        $file = $request->file('file');

        // Load spreadsheet
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();

        $rows = $worksheet->toArray();

        if (count($rows) <= 1) {
            return back()->withErrors(['file' => 'File is empty or invalid.']);
        }

        // Get header row
        $headers = array_map('strtolower', $rows[0]);

        // Required columns
        $required = ['empnum', 'leave_type_id', 'amount'];

        foreach ($required as $column) {
            if (!in_array($column, $headers)) {
                return back()->withErrors([
                    'file' => "Missing required column: {$column}"
                ]);
            }
        }

        // Convert header to index map
        $headerMap = array_flip($headers);

        // Loop rows (skip header)
        for ($i = 1; $i < count($rows); $i++) {

            $row = $rows[$i];

            if (empty($row[$headerMap['empnum']])) {
                continue; // skip empty row
            }

            LeaveAccrualService::accrue(
                $row[$headerMap['empnum']],
                $row[$headerMap['leave_type_id']],
                $row[$headerMap['amount']],
                'Mass Accrual',
                $headerMap['remarks'] ?? null
                    ? $row[$headerMap['remarks']]
                    : null,
                $user->employee->empnum
            );
        }

        return back()->with('success', 'Mass accrual completed successfully.');
    }

}
