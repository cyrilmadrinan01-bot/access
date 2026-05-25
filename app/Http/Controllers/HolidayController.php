<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Holiday;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $query = Holiday::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $sort = $request->sort ?? 'date';
        $direction = $request->direction ?? 'asc';

        $holidays = $query
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('holiday/Index', [
            'holidays' => $holidays,
            'filters' => [
                'search' => $request->search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:Legal,Special',
            'day_type_code' => 'required|string|max:255',
        ]);

        Holiday::create($validated);

        return back()->with('success', 'Holiday added successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        unset($rows[0]); // remove header row

        foreach ($rows as $row) {

            if (empty($row[0])) {
                continue;
            }

            Holiday::updateOrCreate(
                [
                    'name' => $row[0],
                    'date' => $row[1],
                ],
                [
                    'type' => $row[2],
                    'day_type_code' => $row[3],
                ]
            );
        }

        return back()->with('success', 'Holidays imported successfully.');
    }
}
