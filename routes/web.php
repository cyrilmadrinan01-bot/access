<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    DashboardController,
    MedicalController,
    BiometricController,
    TimekeepingController,
    TimekeepingCorrectionController,
    TimekeepingCorrectionApprovalController,
    OvertimeApprovalController,
    OvertimeController,
    UserRoleController,
    LeaveBalanceController,
    LeaveTypeController,
    LeaveController,
    LeaveApprovalController,
    AdminTimekeepingController,
    ShiftCodeController,
    DeviceController,
    EmployeeProfileController,
    EmploymentController,
    PayrollProcessController,
    PayrollController,
    EmployeeController,
    DeviceAssignmentController,
    MyTeamController,
    TimekeepingProcessController,
    OtherIncomeController,
    MedicalApprovalController,
    EmployeeEffectiveController,
    EmployeeImportController,
    EmployeeTerminationController,
    HolidayController,
    EmployeePayslipController,
    ReportController,
    ReportBuilderController,
    ReportMetadataController,
    PicklistController,
    RolePermissionController,
};
use App\Http\Controllers\Api\BiometricLogController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Models\Biometric;
use App\Services\BiometricProcessor;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/biometric', [BiometricController::class, 'index'])->name('biometric.index');
Route::get('/api/employees', [EmployeeController::class, 'getManagers'])->middleware('auth');

// --------------------- Authenticated Routes ---------------------
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // --------------------- Medical ---------------------
    Route::prefix('medical')->group(function () {
        Route::get('/', [MedicalController::class, 'index'])->name('medical');
        Route::get('/create', [MedicalController::class, 'create'])->name('medical.create');
        Route::post('/', [MedicalController::class, 'store'])->name('medical.store');
        Route::get('/{medical}/show', [MedicalController::class, 'show'])->name('medical.show');
        Route::get('/{medical}/edit', [MedicalController::class, 'edit'])->name('medical.edit');
        Route::put('/{medical}', [MedicalController::class, 'update'])->name('medical.update');
        Route::delete('/{medical}', [MedicalController::class, 'destroy'])->name('medical.destroy');
    });

    // --------------------- Timekeeping ---------------------
    Route::prefix('timekeeping')->group(function () {
        Route::get('/', [TimekeepingController::class, 'index'])->name('timekeeping');
        Route::get('/correction/form-data', [TimekeepingCorrectionController::class, 'formData'])->name('timekeeping.correction.form-data');
        Route::post('/correction', [TimekeepingCorrectionController::class, 'store'])->name('timekeeping.correction.store');
        Route::get('/{timekeeping}/corrections', [TimekeepingCorrectionController::class, 'history']);
        Route::patch('/{timekeeping}/correction/delete', [TimekeepingCorrectionController::class, 'deleteCorrection'])->name('timekeeping.correction.delete');
    });

    // --------------------- Leaves ---------------------
    Route::prefix('leaves')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('leaves.index');
        Route::get('/create', [LeaveController::class, 'create'])->name('leaves.create');
        Route::post('/', [LeaveController::class, 'store'])->name('leaves.store');
        Route::put('/{leave}', [LeaveController::class, 'update'])->name('leaves.update');
        Route::delete('/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');
        Route::get('/by-date/{date}', [LeaveController::class, 'byDate'])->name('leaves.by-date');
    });

    // --------------------- Employee ---------------------
   Route::prefix('employees')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PAYSLIPS
        |--------------------------------------------------------------------------
        */

        Route::get('/payslips', [EmployeePayslipController::class,'index'])
            ->name('employees.payslips');

        Route::get('/payslips/{id}/download', [EmployeePayslipController::class,'download'])
            ->name('employee.payslips.download');

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE PROFILE
        |--------------------------------------------------------------------------
        */

        Route::get('/{employee}', [EmployeeProfileController::class, 'show'])
            ->name('employees.profile');

        // Government IDs
        Route::put('/{employee}/government-ids', [EmployeeController::class, 'updateGovernmentIds'])
            ->name('employees.update.government');

        // Editable Sections
        Route::put('/{employee}/employment', [EmployeeController::class, 'updateEmployment'])
            ->name('employees.update.employment');

        Route::put('/{employee}/compensation', [EmployeeController::class, 'updateCompensation'])
            ->name('employees.update.compensation');

        Route::put('/{employee}/personal', [EmployeeController::class, 'updatePersonal'])
            ->name('employees.update.personal');

        Route::put('/{employee}/contact', [EmployeeController::class, 'updateContact'])
            ->name('employees.update.contact');

        Route::put('/{employee}/address', [EmployeeController::class, 'updateAddress'])
            ->name('employees.update.address');

        Route::post('/{empnum}/terminate',[EmployeeTerminationController::class, 'terminate'])
            ->name('employees.terminate');

        Route::post('/{empnum}/rehire',[EmployeeTerminationController::class, 'rehire'])
            ->name('employees.rehire');
    });

    Route::post('/employee/employment', [EmploymentController::class, 'store']);
    Route::post('/employees/{employee}/{section}',[EmployeeEffectiveController::class, 'store'])->name('employees.effective.store');

    // --------------------- Overtime ---------------------
    Route::prefix('overtime')->group(function () {
        Route::get('/', [OvertimeController::class, 'index'])->name('overtime');
        Route::get('/timekeeping/{id}', [OvertimeController::class, 'byTimekeeping'])->name('overtime.by-timekeeping');
        Route::post('/', [OvertimeController::class, 'store'])->name('overtime.store');
        Route::put('/{overtime}', [OvertimeController::class, 'update'])->name('overtime.update');
        Route::delete('/{overtime}', [OvertimeController::class, 'destroy'])->name('overtime.destroy');
    });

    // --------------------- Manager ---------------------
    Route::middleware(['can:manage team'])->group(function () {
        Route::get('/my-team', [MyTeamController::class, 'index'])->name('my-team.index');
    });

    // --------------------- Approvals ---------------------
    Route::prefix('approvals')->group(function () {

        // Timekeeping approvals
        Route::middleware('can:approve correction')->group(function () {
            Route::get('timekeeping', [TimekeepingCorrectionApprovalController::class, 'index'])->name('approvals.timekeeping');
            Route::post('timekeeping/{timekeepingCorrection}/approve', [TimekeepingCorrectionApprovalController::class, 'approve'])->name('approvals.timekeeping.approve');
            Route::post('timekeeping/{timekeepingCorrection}/reject', [TimekeepingCorrectionApprovalController::class, 'reject'])->name('approvals.timekeeping.reject');
            Route::get('timekeeping/approved', [TimekeepingCorrectionApprovalController::class, 'approved'])->name('approvals.timekeeping.approved');
            Route::post('timekeeping/{timekeepingCorrection}/cancel', [TimekeepingCorrectionApprovalController::class, 'cancelApproval'])->name('approvals.timekeeping.cancel');
            Route::post('timekeeping/bulk-approve', [TimekeepingCorrectionApprovalController::class, 'bulkApprove'])->name('approvals.timekeeping.bulk-approve');
            Route::post('timekeeping/cancel-adjustment/{adjustment}', [TimekeepingCorrectionApprovalController::class, 'cancelAdjustment'])->name('approvals.timekeeping.cancel-adjustment');
        });

        // Overtime approvals
        Route::middleware('can:approve overtime')->group(function () {
            Route::get('overtime', [OvertimeApprovalController::class, 'index'])->name('approvals.overtime');
            Route::post('overtime/{overtime}/approve', [OvertimeApprovalController::class, 'approve'])->name('approvals.overtime.approve');
            Route::post('overtime/{overtime}/reject', [OvertimeApprovalController::class, 'reject'])->name('approvals.overtime.reject');
            Route::get('overtime/approved', [OvertimeApprovalController::class, 'approved'])->name('approvals.overtime.approved');
            Route::post('overtime/{overtime}/cancel', [OvertimeApprovalController::class, 'cancelApproval'])->name('approvals.overtime.cancel');
            Route::post('overtime/bulk-approve', [OvertimeApprovalController::class, 'bulkApprove'])->name('approvals.overtime.bulk-approve');
            Route::post('overtime/{overtime}/cancel-rejection', [OvertimeApprovalController::class, 'cancelRejection'])->name('approvals.overtime.cancel-rejection');
        });

        // Leave approvals
        Route::middleware('can:approve leave')->group(function () {
            Route::get('leave-approvals', [LeaveApprovalController::class, 'index'])->name('leave-approvals.index');
            Route::post('leave-approvals/{leave}/approve', [LeaveApprovalController::class, 'approve'])->name('leave-approvals.approve');
            Route::post('leave-approvals/{leave}/reject', [LeaveApprovalController::class, 'reject'])->name('leave-approvals.reject');
            Route::post('leave-approvals/{leave}/cancel-approval', [LeaveApprovalController::class, 'cancelApproval']);
            Route::post('leave-approvals/{leave}/cancel-rejection', [LeaveApprovalController::class, 'cancelRejection']);
        });

        Route::middleware('can:approve medical')->group(function () {
            Route::get('/medical', [MedicalApprovalController::class, 'index'])->name('medical.approval.index');
            Route::post('/medical', [MedicalApprovalController::class, 'store'])->name('medical.approval.store');
            Route::get('/medical/{medical}', [MedicalApprovalController::class, 'show'])->name('medical.approval.show');

            Route::post('/medical/{medical}/approve', [MedicalApprovalController::class, 'approve'])->name('medical.approve');
            Route::post('/medical/{medical}/reject', [MedicalApprovalController::class, 'reject'])->name('medical.reject');
            Route::post('/medical/{medical}/cancel', [MedicalApprovalController::class, 'cancel'])->name('medical.cancel');
        });

    });

    // --------------------- Admin Leave ---------------------
    Route::prefix('admin/leave')->middleware('can:manage leave')->group(function () {
        Route::get('/types', [LeaveTypeController::class, 'index']);
        Route::post('/types', [LeaveTypeController::class, 'store']);
        Route::get('/balances', [LeaveBalanceController::class, 'index'])->name('admin.leave.balances.index');
        Route::post('/balances/adjust', [LeaveBalanceController::class, 'adjust'])->name('admin.leave.balances.adjust');
        Route::post('/balances/mass-accrual', [LeaveBalanceController::class, 'massAccrual'])->name('admin.leave.balances.mass-accrual');
    });

    // --------------------- Admin Payroll ---------------------
    Route::prefix('admin')->group(function () {
        Route::middleware('can:manage holiday')->group(function () {
            Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
            Route::post('/holidays/store', [HolidayController::class, 'store']);
            Route::post('/holidays/import', [HolidayController::class, 'import']);
        });
        Route::middleware('can:manage employees')->group(function () {
            Route::get('/employees', [EmployeeProfileController::class, 'index'])->name('employees.index');
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees/modal-store', [EmployeeController::class, 'modalStore'])->name('employees.modalStore');
            Route::post('/employees/import', [EmployeeImportController::class, 'import'])->name('employees.import');
            Route::get('/employees/search', [EmployeeController::class, 'search'])->name('employees.search');
            //Route::get('/shifts/search', [ShiftCodeController::class, 'search'])->name('shifts.search');
            Route::get('/shift-codes', function () {
                return \App\Models\ShiftCode::select('id', 'shiftCode', 'shiftStart', 'shiftEnd')->get();
            });
            Route::get('/picklists', [PicklistController::class, 'index']);
        });

        Route::middleware('can:manage cutoff')->group(function () {
            Route::post('/payroll-cutoff/{id}/generate-timekeeping', [AdminTimekeepingController::class, 'runTimekeepingGeneration'])->name('admin.cutoff.generate-timekeeping');
            Route::get('/payroll-cutoff', [AdminTimekeepingController::class, 'index'])->name('admin.payroll-cutoff.index');
            Route::post('/payroll-cutoff/add',[AdminTimekeepingController::class, 'store'])->name('admin.payroll-cutoff.store');
        });

        Route::middleware('can:manage shiftcode')->group(function () {
            Route::get('/shiftcodes', [ShiftCodeController::class, 'index'])->name('admin.shiftcode');
            Route::post('/shiftcodes', [ShiftCodeController::class, 'store']);
            Route::put('/shiftcodes/{shiftcode}', [ShiftCodeController::class, 'update']);
            Route::patch('/shiftcodes/{shiftcode}/toggle', [ShiftCodeController::class, 'toggle']);
        });

        Route::middleware('can:run payroll')->group(function () {
            Route::get('/payroll', [PayrollProcessController::class, 'index'])->name('payroll.index');
            //Route::get('/payroll/{cutoff}/timekeeping',[PayrollProcessController::class, 'showTimekeeping'])->name('admin.payroll-cutoff.timekeeping');
            //Route::post('/payroll/timekeeping-process/{cutoff}',[TimekeepingProcessController::class, 'run'])->name('timekeeping.process.run');
            Route::post('/payroll/retry/{cutoff}/{step}', [PayrollProcessController::class, 'retry']);
            Route::post('/payroll/rollback/{cutoff}/{step}', [PayrollProcessController::class, 'rollback']);

            //Route::post('/payroll/other-income/upload', [OtherIncomeController::class, 'upload'])->name('other-income.upload');
            Route::post('/payroll/other-income/skip/{cutoff}', [OtherIncomeController::class, 'skip'])->name('other-income.skip');

            //Route::post('/payroll/upload-employee-deduction',[PayrollProcessController::class, 'uploadEmployeeDeduction'])->name('admin.payroll.employee-deduction.upload');
            //Route::post( '/admin/payroll/{cutoff}/process-medical', [PayrollProcessController::class, 'processMedical'])->name('admin.payroll.medical.process');

            //Route::post('/admin/payroll/{cutoff}/sss/process',[PayrollProcessController::class, 'processSSS'])->name('admin.payroll.sss.process');
            //Route::post('/admin/payroll/{cutoff}/pagibig/process',[PayrollProcessController::class, 'processPagibig'])->name('admin.payroll.pagibig.process');
            //Route::post('/payroll/{cutoff}/generate-philhealth', [PayrollProcessController::class, 'processPhilHealth'])->name('admin.payroll.philhealth.process');

            //Route::post('/payroll/register/process/{cutoff}', [PayrollProcessController::class, 'processPayrollRegister'])->name('admin.payroll.register.process');
        });

        Route::prefix('payroll/{cutoff}')->middleware('can:run payroll')->group(function () {
            Route::get('/', [PayrollController::class, 'index'])->name('payroll.process');

            Route::get('/timekeeping', [PayrollController::class, 'timekeeping'])->name('payroll.timekeeping');
            Route::post('/timekeeping/process', [PayrollController::class, 'processTimekeeping'])->name('timekeeping.run.process');

            Route::get('/other-income', [PayrollController::class, 'otherIncome']);
            Route::post('/other-income/upload', [PayrollController::class, 'uploadOtherIncome'])->name('other-income.upload');

            Route::get('/deduction', [PayrollController::class, 'deduction']);
            Route::post('/deduction/upload', [PayrollController::class, 'uploadEmployeeDeduction'])->name('deduction.upload');

            Route::post('/medical', [PayrollController::class, 'processMedical'])->name('medical.run.process');

            Route::post('/sss', [PayrollController::class, 'processSSS'])->name('sss.run.process');
            Route::post('/sss/skip', [PayrollController::class, 'skipSSS'])->name('sss.skip');

            Route::post('/pagibig', [PayrollController::class, 'processPagibig'])->name('pagibig.run.process');
            Route::post('/philhealth', [PayrollController::class, 'processPhilhealth'])->name('philhealth.run.process');
            Route::post('/payroll', [PayrollController::class, 'processPayroll'])->name('payroll.run.process');
            Route::post('/bank/process', [PayrollController::class, 'processBank'])->name('bank.run.process');
            Route::get('/bank/download', [PayrollController::class, 'downloadBankFile'])->name('bank.download');
            Route::post('/payslip', [PayrollController::class, 'generatePayslip'])->name('payroll.payslip.generate');

            Route::get('/timekeeping/{empnum}', [TimekeepingController::class, 'show'])->name('timekeeping.details');
        });

        //Route::middleware('can:manage device')->group(function () {
        Route::prefix('devices')->middleware('can:manage device')->group(function () {
            Route::get('/', [DeviceController::class, 'index'])->name('devices.index');
            Route::post('/{device}/activate', [DeviceController::class, 'activate'])->name('devices.activate');
            Route::post('/{device}/deactivate', [DeviceController::class, 'deactivate'])->name('devices.deactivate');
            Route::post('/store', [DeviceController::class, 'store']);
            Route::put('/{device}', [DeviceController::class, 'update']);
        
            Route::get('/device-assignment', [DeviceAssignmentController::class, 'index'])->name('device.assignment');
            Route::get('/device-assignment/{device}/users', [DeviceAssignmentController::class, 'usersByDevice'])->name('device.assignment.users');
            Route::post('/device-assignment/assign', [DeviceAssignmentController::class, 'assign'])->name('device.assignment.assign');
            Route::post('/device-assignment/remove', [DeviceAssignmentController::class, 'remove'])->name('device.assignment.remove');
        });

        Route::prefix('picklists')->name('picklists.')->group(function () {
            Route::get('/', [PicklistController::class, 'index'])->name('index');
            Route::get('/{type}', [PicklistController::class, 'show'])->name('show');
            Route::post('/', [PicklistController::class, 'store'])->name('store');
            Route::put('/{picklist}', [PicklistController::class, 'update'])->name('update');
            Route::delete('/{picklist}', [PicklistController::class, 'destroy'])->name('destroy');
            Route::patch('/{picklist}/toggle')->uses([PicklistController::class, 'toggle'])->name('toggle');

            Route::post('/types',[PicklistController::class, 'createType'])->name('types.store');
        });

        // --------------------- Roles ---------------------
        Route::middleware('can:manage roles')->group(function () {
            Route::get('/users/roles', [UserRoleController::class, 'index'])->name('users.roles');
            Route::post('/users/{user}/roles', [UserRoleController::class, 'update'])->name('users.roles.update');
        });

        // --------------------- Permissions ---------------------
        Route::middleware(['auth', 'can:manage permissions'])->group(function () {

            Route::get('/roles/permissions', [RolePermissionController::class, 'index'])
                ->name('roles.permissions');

            Route::post('/roles/{role}/permissions', [RolePermissionController::class, 'update'])
                ->name('roles.permissions.update');
        });

    });
});
/*
Route::middleware(['auth'])->prefix('reports')->group(function () {

        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/create', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/', [ReportController::class, 'store'])->name('reports.store');
        Route::get('/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('/{report}/edit', [ReportController::class, 'edit'])->name('reports.edit');
        Route::put('/{report}', [ReportController::class, 'update'])->name('reports.update');
        Route::delete('/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');
        Route::post('/{report}/share', [ReportController::class, 'share'])->name('reports.share');
    });
*/
Route::middleware(['auth'])->prefix('reports')->group(function () {

        Route::get('/', [ReportBuilderController::class,'index'])->name('reports.index');
        Route::get('/create', [ReportBuilderController::class,'create'])->name('reports.create');
        Route::post('/', [ReportBuilderController::class,'store'])->name('reports.store');
        Route::get('/{report}', [ReportBuilderController::class,'show'])->name('reports.show');
        Route::get('/report-metadata',ReportMetadataController::class);
        Route::post('/{report}/edit', [ReportBuilderController::class, 'edit'])->name('reports.edit');
        Route::delete('/{report}/delete', [ReportBuilderController::class, 'destroy'])->name('reports.destroy');
    });
/*
Route::post('/language/switch', function (Request $request) {

    $locale = $request->language;

    session(['locale' => $locale]);

    if (Auth::check()) {
        Auth::user()->update([
            'language' => $locale
        ]);
    }

    return back();
});
*/



// --------------------- Biometric Processor ---------------------
Route::post('/biometric/logs', [BiometricLogController::class, 'store'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/biometricprocessor', function (BiometricProcessor $processor) {

    $logs = Biometric::where('processed', 'No')->get();

    if ($logs->isEmpty()) {
        return response()->json([
            'status' => 'success',
            'message' => 'No unprocessed logs found.'
        ]);
    }

    $results = [];

    foreach ($logs as $log) {
        try {
            $processor->processLog($log->toArray());

            $results[] = [
                'empnum' => $log->empnum,
                'log_id' => $log->id,
                'message' => 'Processed successfully',
                'timekeeping_id' => $result->id ?? null
            ];
        } catch (\Exception $e) {
            $results[] = [
                'empnum' => $log->empnum,
                'log_id' => $log->id,
                'error' => $e->getMessage()
            ];
        }
    }

    return response()->json([
        'status' => 'completed',
        'results' => $results
    ]);
});

// --------------------- Settings ---------------------
require __DIR__.'/settings.php';
