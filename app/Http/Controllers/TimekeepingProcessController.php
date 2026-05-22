<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PayrollCutOff;
use App\Services\TimekeepingProcessor;
use Illuminate\Http\RedirectResponse;

class TimekeepingProcessController extends Controller
{
    public function run(PayrollCutOff $cutoff, TimekeepingProcessor $processor)
    {
        $processor->process($cutoff);

        return redirect()
            ->route('admin.payroll-cutoff.timekeeping', ['cutoff' => $cutoff->id])
            ->with('success', 'Timekeeping processed successfully.');
    }

}
