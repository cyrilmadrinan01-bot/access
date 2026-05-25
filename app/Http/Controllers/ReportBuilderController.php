<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Services\Reports\DatabaseSchemaService;
use App\Services\Reports\AdvancedReportBuilderService;

class ReportBuilderController extends Controller
{
    public function index()
    {
        return Inertia::render('reports/Index', [
            'reports' => Report::latest()->get(),
        ]);
    }

    public function create(
        DatabaseSchemaService $schema
    ) {
        return Inertia::render('reports/Builder', [
            'modules' => $schema->modules(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required'],
            'module' => ['required'],
            'columns' => ['required', 'array'],
            'joins' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
            'sorts' => ['nullable', 'array'],
            'groups' => ['nullable', 'array'],
            'aggregates' => ['nullable', 'array'],
            'is_public' => ['boolean'],
        ]);

        Report::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('reports.index');
    }

    public function show(
        Report $report,
        AdvancedReportBuilderService $service,
        DatabaseSchemaService $schema
    ) {
        return Inertia::render('reports/View', [
            'report' => $report,
            'data' => $service->generate($report, $schema),
        ]);
    }
}