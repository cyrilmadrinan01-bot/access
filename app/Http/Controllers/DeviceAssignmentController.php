<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Device;
use Inertia\Inertia;

class DeviceAssignmentController extends Controller
{
    public function index()
    {

        return Inertia::render('admin/device/Assignment', [
             'users' => User::select('id', 'name', 'empnum')->get(),
            'devices' => Device::select('id', 'deviceName')->where('status', 'active')->get(),
        ]);
    }

    public function usersByDevice(Device $device)
    {
        return response()->json(
            $device->users()
                ->select('users.empnum', 'users.name')
                ->get()
        );
    }

    public function assign(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:device,id',
            'users' => 'required|array',
        ]);

        $device = Device::findOrFail($request->device_id);

        foreach ($request->users as $empnum) {
            $device->users()->syncWithoutDetaching([$empnum]);
        }

        return back();
    }

    public function remove(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:device,id',
            'users' => 'required|array',
        ]);

        $device = Device::findOrFail($request->device_id);

        $device->users()->detach($request->users);

        return back();
    }
}