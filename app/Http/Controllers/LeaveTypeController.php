<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveType;

class LeaveTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/leave/LeaveTypes', [
            'types' => LeaveType::all()
        ]);
    }

    public function store(Request $request)
    {
        LeaveType::create($request->all());
        return back();
    }
}
