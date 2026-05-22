<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Medical;
use App\Models\MedicalImage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MedicalController extends Controller
{
    public function index(){

        //$medical = Medical::latest()->get(); 
        $medical = Medical::select(
            'medicals.*',
            'statuses.name as status_name'
        )
        ->leftJoin('statuses', 'medicals.status', '=', 'statuses.id')
        ->latest()
        ->get();

        return Inertia::render('medical/Index',[
            'medical' => $medical,
        ]);
    } 

    public function create(){ 

        // Get the current year
        $currentYear = Carbon::now()->year;

        // Query all requests for this year
        $requests = Medical::whereYear('created_at', $currentYear)->get();

        // Sum the total amount
        $totalAmount = $requests->sum('amount');
        $balanceAmount = 5000 - $totalAmount;

        return Inertia::render('medical/Create', [
            'balance' => $balanceAmount,
        ]);
    }

    public function store(Request $request){

        $medical = Medical::select('id')
                            ->orderBy('id', 'desc')
                            ->first();
        $nextId = $medical ? $medical->id + 1 : 1;

        $reqid = 'MRA' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        $validated = $request->validate([
            'empnum' => 'required|string|max:10',
            'empname' => 'required|string|max:225',
            'receiptNumber' => 'required|string|max:50',
            'amount' => 'required|numeric',
            'transactionDate' => 'required|date',
            'employeeNotes' => 'nullable|string',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $medical = Medical::create([
            'reqid' => $reqid,
            'empnum' => $validated['empnum'],
            'empname' => $validated['empname'],
            'receiptNumber' => $validated['receiptNumber'],
            'amount' => $validated['amount'],
            'transactionDate' => $validated['transactionDate'],
            'employeeNotes' => $validated['employeeNotes'] ?? null,
            'status' => 'Pending',
            'processed' => 'No',
        ]);

        if($request->hasFile('images')) {
            foreach($request->file('images') as $img) {
                $path = $img->store('medical', 'public');

                MedicalImage::create([
                    'medical_id' => $medical->id,
                    'image_path' => $path,
                    'original_name' => $img->getClientOriginalName(),
                ]);
            }
        }

        return redirect()->route('medical')->with('message', 'Medical re-imbursement created successfully.');
    }

    public function show($id){
        $medical = Medical::with('images')->findOrFail($id);
        $medicalImages = $medical->images->pluck('image_path')->all();

        return Inertia::render('medical/Show', [
            'medical' => [
            'reqid' => $medical->reqid,
            'empnum' => $medical->empnum,
            'empname' => $medical->empname,
            'receiptNumber' => $medical->receiptNumber,
            'amount' => $medical->amount,
            'transactionDate' => Carbon::parse($medical->transactionDate)->format('Y-m-d'),
            'employeeNotes' => $medical->employeeNotes,
            'images' => $medicalImages, 
            ]
        ]);
    }

    public function edit(Medical $medical){
        $medicalImages = MedicalImage::where('medical_id', $medical->id)
                                ->get(['id', 'image_path']); 

        $year = now()->year;
        $totalForYear = Medical::where('empnum', $medical->empnum)
        ->whereYear('created_at', $year)
        ->sum('amount');
        $remaining = 5000 - $totalForYear;

        return Inertia::render('medical/Edit', [
            'medical' => $medical,
            'medicalImages' => $medicalImages,
            'balance'     => $remaining,
        ]);
    }

    public function update(Request $request, Medical $medical)
    {
        // Validate the basic fields
        $request->validate([
            'empnum' => 'required|string|max:50',
            'empname' => 'required|string|max:100',
            'receiptNumber' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'transactionDate' => 'required|date',
            'employeeNotes' => 'nullable|string',
            'new_images.*' => 'nullable|file|image|max:5120', // max 5MB per image
            'replaced_images.*.file' => 'nullable|file|image|max:5120',
            'deleted_images.*' => 'nullable|integer|exists:medical_images,id',
        ]);

        DB::transaction(function() use ($request, $medical) {

            // 1️⃣ Update medical record
            $medical->update([
                'empnum' => $request->input('empnum'),
                'empname' => $request->input('empname'),
                'receiptNumber' => $request->input('receiptNumber'),
                'amount' => $request->input('amount'),
                'transactionDate' => $request->input('transactionDate'),
                'employeeNotes' => $request->input('employeeNotes'),
            ]);

            // 2️⃣ Delete selected images
            if ($request->filled('deleted_images')) {
                $imagesToDelete = MedicalImage::whereIn('id', $request->deleted_images)->get();
                foreach ($imagesToDelete as $img) {
                    if (Storage::disk('public')->exists($img->image_path)) {
                    Storage::disk('public')->delete($img->image_path);
                }
                    $img->delete();
                }
            }

            // 3️⃣ Replace images
            if ($request->filled('replaced_images')) {
                foreach ($request->replaced_images as $item) {
                    $existingImage = MedicalImage::find($item['id']);
                    if (!$existingImage) continue;

                    // Delete old file
                    if ($existingImage->image_path && Storage::exists($existingImage->image_path)) {
                        Storage::delete($existingImage->image_path);
                    }

                    // Store new file
                    if (!empty($item['file'])) {
                        $path = $item['file']->store('medical', 'public');

                        $existingImage->update([
                            'image_path' => $path,
                            'original_name' => $item['file']->getClientOriginalName(),
                        ]);
                    }
                }
            }

            // 4️⃣ Add new images
            if ($request->hasFile('new_images')) {
                foreach ($request->file('new_images') as $file) {
                    $path = $file->store('medical', 'public');
                    MedicalImage::create([
                        'medical_id' => $medical->id,
                        'image_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            }
        });

        //return redirect()->route('medical')->with('message', 'Medical reimbursement updated successfully.');
        return response()->json([
            'message' => 'Medical reimbursement updated successfully.'
        ]);
    }

    public function destroy(Medical $medical)
    {
        // Delete associated images first
        foreach ($medical->images as $image) {
            // Delete the file from storage/public/medical
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
            // Delete the database record
            $image->delete();
        }

        // Delete the medical record
        $medical->delete();

        // Return JSON response for Axios
        return response()->json([
            'message' => 'Medical record and associated images deleted successfully.'
        ]);
    }
}
