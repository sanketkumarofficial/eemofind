<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ResourcePageController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService)
    {
    }

    public function index(Request $request)
    {
        [$key, $model, $title] = $this->resolve($request);
        $query = $model::query()->latest();
        $records = $query->paginate(20)->withQueryString();

        return view('admin.resources.index', compact('key', 'title', 'records'));
    }

    public function create(Request $request)
    {
        [$key, , $title] = $this->resolve($request);
        return view('admin.resources.form', compact('key', 'title'));
    }

    public function store(Request $request)
    {
        [$key, $model] = $this->resolve($request);
        $data = $this->validatedPayload($request, $model);
        $record = $model::create($data);
        $this->activityLogService->handle(['event' => $key . '.created', 'id' => $record->id]);

        return redirect()->route('admin.' . $key . '.show', $record)->with('success', 'Record created.');
    }

    public function show(Request $request, string $id)
    {
        [$key, $model, $title] = $this->resolve($request);
        $record = $model::findOrFail($id);

        return view('admin.resources.show', compact('key', 'title', 'record'));
    }

    public function edit(Request $request, string $id)
    {
        [$key, $model, $title] = $this->resolve($request);
        $record = $model::findOrFail($id);

        return view('admin.resources.form', compact('key', 'title', 'record'));
    }

    public function update(Request $request, string $id)
    {
        [$key, $model] = $this->resolve($request);
        $record = $model::findOrFail($id);
        $record->fill($this->validatedPayload($request, $model))->save();
        $this->activityLogService->handle(['event' => $key . '.updated', 'id' => $record->id]);

        return redirect()->route('admin.' . $key . '.show', $record)->with('success', 'Record updated.');
    }

    public function destroy(Request $request, string $id)
    {
        [$key, $model] = $this->resolve($request);
        $model::findOrFail($id)->delete();

        return redirect()->route('admin.' . $key . '.index')->with('success', 'Record deleted.');
    }

    public function action(Request $request, string $id, string $action)
    {
        [$key, $model] = $this->resolve($request);
        $record = $model::findOrFail($id);
        $this->activityLogService->handle(['event' => $key . '.' . $action, 'id' => $record->id, 'payload' => $request->all()]);

        return back()->with('success', Str::headline($action) . ' completed.');
    }

    public function export(Request $request)
    {
        [$key] = $this->resolve($request);
        return response()->json(['message' => Str::headline($key) . ' export queued.']);
    }

    public function bulkAction(Request $request)
    {
        [$key] = $this->resolve($request);
        $request->validate(['action' => ['required', 'string'], 'ids' => ['array']]);
        $this->activityLogService->handle(['event' => $key . '.bulk-action', 'payload' => $request->all()]);

        return back()->with('success', 'Bulk action queued.');
    }

    private function resolve(Request $request): array
    {
        $key = Str::before($request->route()->getName(), '.');
        $key = Str::after($request->route()->getName(), 'admin.');
        $key = Str::before($key, '.');
        $class = 'App\\Models\\' . Str::studly(Str::singular(str_replace('-', '_', $key)));
        $aliases = [
            'tracking-groups' => \App\Models\TrackingGroup::class,
            'support-tickets' => \App\Models\SupportTicket::class,
            'sos-events' => \App\Models\SosEvent::class,
            'emergency-contacts' => \App\Models\EmergencyContact::class,
            'push-tokens' => \App\Models\PushToken::class,
            'pairing-codes' => \App\Models\PairingCode::class,
            'activity-logs' => \App\Models\ActivityLog::class,
            'notification-logs' => \App\Models\NotificationLog::class,
            'notifications' => \App\Models\AdminNotification::class,
        ];
        $model = $aliases[$key] ?? $class;
        abort_unless(class_exists($model), 404);

        return [$key, $model, Str::headline($key)];
    }

    private function validatedPayload(Request $request, string $model): array
    {
        $table = (new $model)->getTable();
        $columns = collect(Schema::getColumnListing($table))->reject(fn ($column) => in_array($column, ['id', 'created_at', 'updated_at'], true));
        $rules = $columns->mapWithKeys(fn ($column) => [$column => ['nullable']])->all();

        return Arr::only($request->validate($rules), $columns->all());
    }
}