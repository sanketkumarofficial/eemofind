<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobileResourceController extends Controller
{
    private array $models = [
        'devices' => \App\Models\Device::class,
        'tracking' => \App\Models\DeviceSnapshot::class,
        'groups' => \App\Models\TrackingGroup::class,
        'pairing' => \App\Models\PairingCode::class,
        'subscriptions' => \App\Models\Subscription::class,
        'payments' => \App\Models\Payment::class,
        'notifications' => \App\Models\AdminNotification::class,
        'tickets' => \App\Models\SupportTicket::class,
        'sos' => \App\Models\SosEvent::class,
        'geofences' => \App\Models\Geofence::class,
        'emergency-contacts' => \App\Models\EmergencyContact::class,
        'referrals' => \App\Models\Referral::class,
        'faq' => \App\Models\Faq::class,
    ];

    public function index(Request $request, string $module)
    {
        return response()->json($this->model($module)::query()->latest()->paginate($request->integer('per_page', 25)));
    }

    public function store(Request $request, string $module)
    {
        $record = $this->model($module)::create($request->all());
        return response()->json($record, 201);
    }

    public function show(string $module, string $id)
    {
        return response()->json($this->model($module)::findOrFail($id));
    }

    public function update(Request $request, string $module, string $id)
    {
        $record = $this->model($module)::findOrFail($id);
        $record->fill($request->all())->save();
        return response()->json($record);
    }

    public function destroy(string $module, string $id)
    {
        $this->model($module)::findOrFail($id)->delete();
        return response()->noContent();
    }

    private function model(string $module): string
    {
        abort_unless(isset($this->models[$module]), 404, 'Unknown API module.');
        return $this->models[$module];
    }
}