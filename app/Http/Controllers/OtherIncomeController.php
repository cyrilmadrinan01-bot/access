<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\OtherIncome;
use App\Models\PayrollCutOff;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OtherIncomeController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'cutoff_id' => 'required|integer',
            'is_taxable' => 'required|in:0,1',
        ]);

        $file = $request->file('file');
        $cutoffId = $request->cutoff_id;
        $isTaxable = (int) $request->is_taxable;

        DB::transaction(function () use ($file, $cutoffId, $isTaxable) {

            // ✅ DELETE existing records for this cutoff
            OtherIncome::where('cutoff_id', $cutoffId)->delete();

            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());

            $sheet = $spreadsheet->getSheet(0);
            $rows = $sheet->toArray(null, true, true, true);

            foreach ($rows as $index => $row) {
                if ($index === 1) continue;

                OtherIncome::create([
                    'cutoff_id' => $cutoffId,
                    'empnum' => $row['A'] ?? null,
                    'empname' => $row['B'] ?? null,
                    'income_type' => $row['C'] ?? null,
                    'amount' => $row['D'] ?? 0,
                    'is_taxable' => $isTaxable,
                    'uploaded_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Other income uploaded successfully.');
    }

    public function skip($cutoffId)
    {
        // Mark step as completed in your payroll status logic
        // Example:
        // PayrollStep::markCompleted($cutoffId, 'other_income');

        return back();
    }
}
