<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Medical;

class MedicalApprovalController extends Controller
{
    public function index()
    {
        return Inertia::render('approvals/Medical', [
            'pending' => Medical::where('status', '2')->latest()->get(),
            'approved' => Medical::where('status', '3')->latest()->get(),
            'rejected' => Medical::where('status', '4')->latest()->get(),
        ]);
    }

    public function show(Medical $medical)
    {
        $medical->load('images');
        // Fetch other records with the same receiptNumber (excluding self)
        $duplicates = Medical::with('images')
        ->where('receiptNumber', $medical->receiptNumber)
        ->where('id', '!=', $medical->id)
        ->get();

        return Inertia::render('approvals/MedicalDetails', [
            'medical' => $medical,
            'duplicates' => $duplicates,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'reqid' => 'required',
            'empnum' => 'required',
            'empname' => 'required',
            'receiptNumber' => [
                'required',
                'unique:medicals,receiptNumber'
            ],
            'amount' => 'required|numeric|min:0',
            'transactionDate' => 'required|date',
        ], [
            'receiptNumber.unique' => 'This invoice/receipt number was already submitted.'
        ]);

        Medical::create([
            ...$request->all(),
            'status' => 'PENDING'
        ]); 

        return back()->with('success', 'Medical request submitted.');
    }

    public function approve(Request $request, Medical $medical)
    {
        $request->validate([
            'payout' => 'required|date',
            'adminNotes' => 'nullable|string'
        ]);

        $medical->update([
            'status' => 'APPROVED',
            'payout' => $request->payout,
            'adminNotes' => $request->adminNotes,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Medical request approved.');
    }

    public function reject(Request $request, Medical $medical)
    {
        $request->validate([
            'adminNotes' => 'required|string'
        ]);

        $medical->update([
            'status' => 'REJECTED',
            'adminNotes' => $request->adminNotes,
            'payout' => null,
        ]);

        return back()->with('success', 'Medical request rejected.');
    }

    public function cancel(Medical $medical)
    {
        $medical->update([
            'status' => 'PENDING',
            'payout' => null
        ]);

        return back()->with('success', 'Medical request approval cancelled.');
    }
}
