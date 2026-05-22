<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/device/Index', [
            'devices' => Device::orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'deviceName' => 'required|string|max:255',
            'deviceType' => 'required|string|max:255',
            'deviceIp'   => 'required|string|max:255',
            'location'   => 'required|string|max:255',
        ]);

        Device::create($request->all());

        return back()->with('success', 'Device added successfully.');
    }

    public function update(Request $request, Device $device)
    {
        $request->validate([
            'deviceName' => 'required|string|max:255',
            'deviceType' => 'required|in:Clock In,Clock Out',
            'deviceIp'   => 'required|string|max:255',
            'location'   => 'required|string|max:255',
        ]);

        $device->update($request->all());

        return back()->with('success', 'Device updated successfully.');
    }

    public function activate(Device $device)
    {
        if ($device->status !== Device::STATUS_INACTIVE) {
            return back()->with('error', 'Device is already active.');
        }

        $device->update([
            'status' => Device::STATUS_ACTIVE,
        ]);

        return back()->with('success', 'Device activated.');
    }

    public function deactivate(Device $device)
    {
        if ($device->status !== Device::STATUS_ACTIVE) {
            return back()->with('error', 'Device is already inactive.');
        }

        $device->update([
            'status' => Device::STATUS_INACTIVE,
        ]);

        return back()->with('success', 'Device deactivated.');
    }
}
