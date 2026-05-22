<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shiftcode;
use Inertia\Inertia;

class ShiftCodeController extends Controller
{
    public function index() {
        return Inertia::render('admin/shiftcode/Index', [
            'shiftcodes' => Shiftcode::orderBy('shiftCode')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shiftCode' => 'required|string|unique:shiftcodes,shiftCode',
            'shiftStart' => 'required',
            'shiftEnd' => 'required',
            'hoursWorked' => 'required|numeric',
            'withNd' => 'required|string',
            'ndStart' => 'required',
            'ndEnd' => 'required',
            'regHours' => 'required|numeric',
            'ndHours'  => 'required|numeric',
            'workDay' => 'required|string',
            'restDay' => 'required|string',
            'usShift' => 'required|string',
            'sameDay' => 'required|string',
            'ndCrossDayStart' => 'required',
            'ndCrossDayEnd' => 'required',
            'rotatingShift' => 'required|string',
            'group' => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $data['ndCrossDayStart'] ??= '00:00:00';
        $data['ndCrossDayEnd']   ??= '00:00:00';

        Shiftcode::create($data);

        return back()->with('success', 'Shiftcode created');
    }


    public function update(Request $request, Shiftcode $shiftcode)
    {
        $data = $request->validate([
            'shiftCode' => 'required|string|unique:shiftcodes,shiftCode,' . $shiftcode->id,
            'shiftStart' => 'required',
            'shiftEnd' => 'required',
            'hoursWorked' => 'required|numeric',
            'restDay' => 'nullable|string',
            'withNd' => 'required|string',
            'ndStart' => 'required',
            'ndEnd' => 'required',
            'regHours'  => 'required|numeric',
            'ndHours'  => 'required|numeric',
            'workDay'   => 'required|string',
            'restDay' => 'required|string',
            'usShift'    => 'required|string',
            'sameDay'    => 'required|string',
            'ndCrossDayStart' => 'required',
            'ndCrossDayEnd' => 'required',
            'rotatingShift'    => 'required|string',
            'group'    => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $shiftcode->update($data);

        return back()->with('success', 'Shiftcode updated');
    }

    public function toggle(Shiftcode $shiftcode)
    {
        $shiftcode->update([
            'is_active' => ! $shiftcode->is_active,
        ]);

        return back();
    }
}
