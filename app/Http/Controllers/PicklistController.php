<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Picklist;

class PicklistController extends Controller
{
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
}
