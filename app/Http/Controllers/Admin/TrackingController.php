<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;

class TrackingController extends Controller
{
    public function index()
    {
        $devices = Device::latest()->get();

        return view('admin.tracking.index', compact('devices'));
    }

    public function history()
    {
        $devices = Device::latest()->get();

        return view('admin.tracking.history', compact('devices'));
    }
}