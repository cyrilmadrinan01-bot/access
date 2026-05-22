<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyTeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // assuming user is linked to employee via empnum or employee_id
        $employee = Employee::where('empnum', $user->empnum)->firstOrFail();

        $team = Employee::where('managerId', $employee->empnum)
            ->orderBy('name')
            ->get([
                'id',
                'empnum',
                'name',
                'businessTitle',
                'deptName',
                'shiftCode'
            ]);

        return Inertia::render('myTeam/Index', [
            'team' => $team,
        ]);
    }
}
