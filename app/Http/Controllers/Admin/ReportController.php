<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\TrackingGroup;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $payments = Payment::with('user')->when($request->from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))->when($request->to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))->latest()->paginate(25)->withQueryString();

        return view('admin.reports.index', compact('payments'));
    }

    public function export(Request $request, string $type, string $format)
    {
        abort_unless($request->user()->can('reports.export'), 403);
        [$headings, $rows] = $this->dataset($type);
        if ($format === 'pdf') {
            return Pdf::loadView('admin.reports.pdf', compact('type', 'headings', 'rows'))->setPaper('a4', 'landscape')->download("{$type}-report.pdf");
        }
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        return Excel::download(new ArrayExport($headings, $rows), "{$type}-report.{$format}");
    }

    private function dataset(string $type): array
    {
        return match ($type) {
            'users' => [['ID', 'Name', 'Email', 'Mobile', 'Status', 'Created'], User::get()->map(fn ($x) => [$x->id, $x->name, $x->email, $x->mobile, $x->status, $x->created_at])],
            'devices' => [['ID', 'Name', 'IMEI', 'Type', 'User', 'Enabled'], Device::with('user')->get()->map(fn ($x) => [$x->id, $x->name, $x->imei, $x->device_type, $x->user?->name, $x->is_enabled ? 'Yes' : 'No'])],
            'groups' => [['ID', 'Name', 'Owner', 'Status', 'Created'], TrackingGroup::with('owner')->get()->map(fn ($x) => [$x->id, $x->name, $x->owner->name, $x->status, $x->created_at])],
            'subscriptions' => [['ID', 'User', 'Plan', 'Amount', 'Start', 'End', 'Status'], Subscription::with(['user', 'plan'])->get()->map(fn ($x) => [$x->id, $x->user->name, $x->plan->name, $x->amount, $x->start_date, $x->end_date, $x->status])],
            'payments' => [['ID', 'User', 'Order', 'Payment', 'Amount', 'Status', 'Paid'], Payment::with('user')->get()->map(fn ($x) => [$x->id, $x->user->name, $x->order_id, $x->payment_id, $x->amount, $x->status, $x->paid_at])],
            'tickets' => [['Number', 'User', 'Category', 'Priority', 'Status', 'Subject'], SupportTicket::with('user')->get()->map(fn ($x) => [$x->ticket_number, $x->user->name, $x->category, $x->priority, $x->status, $x->subject])],
            default => abort(404),
        };
    }
}
