<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Picklist;
use Inertia\Inertia;

class PicklistController extends Controller
{
    /*
    public function index()
    {
        $types = [
            'company_code',
            'employee_class',
            'location',
            'country',
            'job_code',
            'pay_grade',
            'department',
            'employee_status',
            'marital_status',
            'gender',
            'nationality',
            'employee_type',
        ];

        $data = [];

        foreach ($types as $type) {
            $data[$type] = Picklist::where('type', $type)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        return response()->json($data);
    }
        */

    public function index()
    {
        $types = Picklist::query()
            ->select('type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('type')
            ->orderBy('type')
            ->get();

        return Inertia::render(
            'admin/picklists/Index',
            [
                'types' => $types,
            ]
        );
    }

    public function show(string $type)
    {
        return Inertia::render(
            'admin/picklists/Show',
            [
                'type' => $type,

                'picklists' => Picklist::query()
                    ->where('type', $type)
                    ->orderBy('sort_order')
                    ->get(),
            ]
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required',
            'code' => 'required',
            'label' => 'required',
            'parent_code' => 'nullable',
            'sort_order' => 'nullable|integer',
        ]);

        Picklist::create($validated);

        return back()->with(
            'success',
            'Picklist created successfully.'
        );
    }

    public function update(
        Request $request,
        Picklist $picklist
    ) {
        $validated = $request->validate([
            'code' => 'required',
            'label' => 'required',
            'parent_code' => 'nullable',
            'sort_order' => 'nullable|integer',
        ]);

        $picklist->update($validated);

        return back()->with(
            'success',
            'Picklist updated successfully.'
        );
    }

    public function destroy(Picklist $picklist)
    {
        $picklist->delete();

        return back()->with(
            'success',
            'Picklist deleted successfully.'
        );
    }

    public function toggle(Picklist $picklist)
    {
        $picklist->update([
            'is_active' => !$picklist->is_active,
        ]);

        return back();
    }

    public function createType(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:255'],
        ]);

        if (
            Picklist::where('type', strtoupper($validated['type']))->exists()
        ) {
            return back()->withErrors([
                'type' => 'Type already exists.',
            ]);
        }

        Picklist::create([
            'type' => strtoupper($validated['type']),
            'code' => 'DEFAULT',
            'label' => 'Default',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return back()->with(
            'success',
            'Picklist type created successfully.'
        );
    }
}
