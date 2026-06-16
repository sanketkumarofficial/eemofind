<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\TrackingGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $metrics = [
            'total_users' => User::count(), 'active_users' => User::where('status', 'active')->count(), 'suspended_users' => User::where('status', 'suspended')->count(),
            'total_devices' => Device::count(), 'online_devices' => Device::whereHas('snapshot', fn ($q) => $q->where('is_online', true))->count(),
            'offline_devices' => Device::whereDoesntHave('snapshot', fn ($q) => $q->where('is_online', true))->count(), 'active_groups' => TrackingGroup::where('status', 'active')->count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(), 'expired_subscriptions' => Subscription::where('status', 'expired')->count(),
            'open_tickets' => SupportTicket::whereIn('status', ['open', 'in_progress', 'reopened'])->count(), 'total_revenue' => Payment::where('status', 'success')->sum('amount'),
            'monthly_revenue' => Payment::where('status', 'success')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'today_revenue' => Payment::where('status', 'success')->whereDate('paid_at', today())->sum('amount'),
        ];
        $periodExpression = DB::getDriverName() === 'mysql' ? "DATE_FORMAT(paid_at, '%Y-%m')" : "strftime('%Y-%m', paid_at)";
        $revenue = Payment::where('status', 'success')->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())->selectRaw("{$periodExpression} period, SUM(amount) total")->groupBy('period')->orderBy('period')->get();

        return view('dashboard', compact('metrics', 'revenue') + ['recentUsers' => User::latest()->limit(5)->get(), 'recentPayments' => Payment::with('user')->latest()->limit(5)->get(), 'recentTickets' => SupportTicket::with('user')->latest()->limit(5)->get()]);
    }
}
