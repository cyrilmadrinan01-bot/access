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
            'reports' => Report::latest()->get([
                'id', 'name', 'module', 'is_public', 'created_at'
            ]),
        ]);
    }

    public function create(DatabaseSchemaService $schema)
    {
        return Inertia::render('reports/Builder', [
            'modules' => $schema->modules(),
            'fieldIndex' => $schema->allFields(),
        ]);
    }

    public function store(Request $request, DatabaseSchemaService $schema)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'module' => ['required', 'string'],
            'columns' => ['required', 'array'],
            'joins' => ['array'],
            'filters' => ['array'],
            'sorts' => ['array'],
            'groups' => ['array'],
            'aggregates' => ['array'],
            'is_public' => ['boolean'],
        ]);

        $allowed = array_keys($schema->allFields());

        $validated['columns'] = array_values(array_intersect($validated['columns'], $allowed));
        $validated['groups'] = array_values(array_intersect($validated['groups'] ?? [], $allowed));

        $validated['filters'] = array_values(array_filter($validated['filters'] ?? [], fn($f) =>
            isset($f['field']) && in_array($f['field'], $allowed)
        ));

        $validated['sorts'] = array_values(array_filter($validated['sorts'] ?? [], fn($s) =>
            isset($s['field']) && in_array($s['field'], $allowed)
        ));

        $report = Report::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('reports.show', $report);
    }

    public function show(
        Report $report,
        AdvancedReportBuilderService $service,
        DatabaseSchemaService $schema
    ) {
        return Inertia::render('reports/View', [
            'report' => $report,
            'data' => $service->generate($report, $schema),
            'schema' => $schema->modules(),
        ]);
    }

    public function destroy(Report $report)
    {
        $report->delete();

        return redirect()->route('reports.index');
    }
}