<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportShare;
use App\Models\User;
use App\Services\Reports\ReportComputeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::query()
            ->with('owner')
            ->latest()
            ->get();

        return Inertia::render('reports/Index', [
            'reports' => $reports,
        ]);
    }

        public function create()
    {
        return Inertia::render('reports/Builder', [
            'modules' => [
                'employees',
                'payroll',
                'attendance',
            ],

            'availableColumns' => [
                'employees' => [
                    'empnum',
                    'firstName',
                    'lastName',
                    'deptName',
                    'jobTitle',
                    'managerId',
                ],

                'payroll' => [
                    'basicPay',
                    'grossPay',
                    'netPay',
                    'tax',
                    'sss',
                    'philhealth',
                    'pagibig',
                ],

                'attendance' => [
                    'date',
                    'timeIn',
                    'timeOut',
                    'late',
                    'undertime',
                    'overtime',
                ],
            ],
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required'],
            'module' => ['required'],
            'columns' => ['required', 'array'],
            'filters' => ['nullable', 'array'],
            'sorts' => ['nullable', 'array'],
            'is_public' => ['boolean'],
        ]);

        Report::create([
            ...$validated,
            'user_id' => Auth::user()->id(),
        ]);

        return redirect()
            ->route('reports.index')
            ->with('success', 'Report created successfully');
    }

    public function show(
        Report $report,
        ReportComputeService $service
    ) {
        $data = $service->generate($report);

        return Inertia::render('reports/View', [
            'report' => $report,
            'data' => $data,
        ]);
    }

    public function edit(Report $report)
    {
        return Inertia::render('reports/Builder', [
            'report' => $report,
            'modules' => [
                'employees',
                'payroll',
                'attendance',
            ],
        ]);
    }

    public function update(Request $request, Report $report)
    {
        $validated = $request->validate([
            'name' => ['required'],
            'module' => ['required'],
            'columns' => ['required', 'array'],
            'filters' => ['nullable', 'array'],
            'sorts' => ['nullable', 'array'],
            'is_public' => ['boolean'],
        ]);

        $report->update($validated);

        return back()->with('success', 'Report updated');
    }

    public function destroy(Report $report)
    {
        $report->delete();

        return back()->with('success', 'Report deleted');
    }

    public function share(Request $request, Report $report)
    {
        $request->validate([
            'user_id' => ['required'],
            'permission' => ['required'],
        ]);

        ReportShare::updateOrCreate(
            [
                'report_id' => $report->id,
                'shared_to_user_id' => $request->user_id,
            ],
            [
                'permission' => $request->permission,
            ]
        );

        return back()->with('success', 'Report shared');
    }
}
