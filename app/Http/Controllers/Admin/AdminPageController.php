<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Models\TrackingGroup;
use App\Models\User;
use Illuminate\Http\Request;

class AdminPageController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'Total Users' => User::count(),
            'Active Users' => User::whereNull('deleted_at')->count(),
            'Suspended Users' => 0,
            'Total Devices' => Device::count(),
            'Online Devices' => Device::where('status', 'online')->count(),
            'Offline Devices' => Device::where('status', 'offline')->count(),
            'Groups' => TrackingGroup::count(),
            'Active Subscriptions' => 0,
            'Expired Subscriptions' => 0,
            'Open Tickets' => SupportTicket::where('status', 'open')->count(),
            'Revenue' => Payment::where('status', 'paid')->sum('amount'),
            'Monthly Revenue' => Payment::where('status', 'paid')->whereMonth('created_at', now()->month)->sum('amount'),
            "Today's Revenue" => Payment::where('status', 'paid')->whereDate('created_at', today())->sum('amount'),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function report(string $type)
    {
        return view('admin.report', ['type' => $type]);
    }

    public function systemHealth(?string $section = null)
    {
        return view('admin.system-health', ['section' => $section ?? 'queues']);
    }
}