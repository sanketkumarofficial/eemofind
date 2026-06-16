<?php

namespace App\Http\Controllers\Admin;

use App\Events\DomainAction;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Faq;
use App\Models\Geofence;
use App\Models\Plan;
use App\Models\TrackingGroup;
use App\Models\User;
use App\Rules\SafeEmail;
use App\Services\ActivityService;
use App\Services\PairingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
{
    public function index(Request $request, string $module)
    {
        $config = $this->config($module);
        $this->authorizePermission($module, 'view');
        $query = $config['model']::query();
        foreach ($config['with'] ?? [] as $relation) {
            $query->with($relation);
        }
        if ($search = $request->string('search')->trim()->value()) {
            $query->where(fn ($q) => collect($config['search'])->each(fn ($column) => $q->orWhere($column, 'like', "%{$search}%")));
        }
        $records = $query->latest()->paginate(20)->withQueryString();

        return view('admin.resources.index', compact('module', 'config', 'records'));
    }

    public function create(string $module)
    {
        $config = $this->config($module);
        $this->authorizePermission($module, 'create');

        return view('admin.resources.form', compact('module', 'config') + ['record' => null, 'options' => $this->options($module)]);
    }

    public function store(Request $request, string $module, ActivityService $activity, PairingService $pairing)
    {
        $config = $this->config($module);
        $this->authorizePermission($module, 'create');
        $data = $request->validate($this->rules($module));
        $record = DB::transaction(function () use ($module, $config, $data, $request, $pairing) {
            if ($module === 'users' && $request->hasFile('profile_image')) {
                $data['profile_image'] = $request->file('profile_image')->store('users', 'public');
            }
            $record = $config['model']::create($data);
            if ($module === 'groups') {
                $record->members()->attach($record->owner_id, ['role' => 'owner', 'joined_at' => now()]);
                $pairing->refresh($record);
            }

            return $record;
        });
        $activity->log($module, 'created', "Created {$config['singular']} #{$record->id}.", $record);
        DomainAction::dispatch($request->user(), "{$module}.created", "{$config['singular']} created", "{$config['singular']} was created successfully.", ['id' => $record->id]);

        return redirect()->route('admin.resources.index', $module)->with('success', "{$config['singular']} created.");
    }

    public function show(string $module, int $id)
    {
        $config = $this->config($module);
        $this->authorizePermission($module, 'view');
        $record = $config['model']::findOrFail($id);

        return view('admin.resources.show', compact('module', 'config', 'record'));
    }

    public function edit(string $module, int $id)
    {
        $config = $this->config($module);
        $this->authorizePermission($module, 'update');
        $record = $config['model']::findOrFail($id);

        return view('admin.resources.form', compact('module', 'config', 'record') + ['options' => $this->options($module)]);
    }

    public function update(Request $request, string $module, int $id, ActivityService $activity)
    {
        $config = $this->config($module);
        $this->authorizePermission($module, 'update');
        $record = $config['model']::findOrFail($id);
        $data = $request->validate($this->rules($module, $record));
        if ($module === 'users' && empty($data['password'])) {
            unset($data['password']);
        }
        if ($module === 'users' && $request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('users', 'public');
        }
        $record->update($data);
        $activity->log($module, 'updated', "Updated {$config['singular']} #{$record->id}.", $record);

        return redirect()->route('admin.resources.index', $module)->with('success', "{$config['singular']} updated.");
    }

    public function destroy(string $module, int $id, ActivityService $activity)
    {
        $config = $this->config($module);
        $this->authorizePermission($module, 'delete');
        $record = $config['model']::findOrFail($id);
        $activity->log($module, 'deleted', "Deleted {$config['singular']} #{$record->id}.", $record);
        $record->delete();

        return back()->with('success', "{$config['singular']} deleted.");
    }

    public function userAction(Request $request, User $user, string $action)
    {
        abort_unless($request->user()->can('users.update'), 403);
        match ($action) {
            'suspend' => $user->update(['status' => 'suspended', 'force_logout_at' => now()]),
            'activate' => $user->update(['status' => 'active']),
            'force-logout' => $user->update(['force_logout_at' => now()]),
            'reset-password' => $user->update(['password' => $request->validate(['password' => ['required', 'min:8', 'confirmed']])['password'], 'force_logout_at' => now()]),
            default => abort(404),
        };
        $user->tokens()->delete();

        return back()->with('success', 'User action completed.');
    }

    private function config(string $module): array
    {
        return match ($module) {
            'users' => ['model' => User::class, 'title' => 'Users', 'singular' => 'User', 'search' => ['name', 'email', 'mobile'], 'columns' => ['name', 'email', 'mobile', 'status', 'last_login_at'], 'fields' => ['name' => 'text', 'mobile' => 'text', 'email' => 'email', 'password' => 'password', 'profile_image' => 'file', 'gender' => 'select:male,female,other', 'date_of_birth' => 'date', 'status' => 'select:active,suspended']],
            'devices' => ['model' => Device::class, 'title' => 'Devices', 'singular' => 'Device', 'search' => ['name', 'imei', 'sim_number'], 'with' => ['user'], 'columns' => ['name', 'imei', 'device_type', 'model', 'is_enabled'], 'fields' => ['name' => 'text', 'imei' => 'text', 'device_type' => 'text', 'model' => 'text', 'firmware_version' => 'text', 'sim_number' => 'text', 'user_id' => 'relation:users', 'is_enabled' => 'boolean']],
            'groups' => ['model' => TrackingGroup::class, 'title' => 'Groups', 'singular' => 'Group', 'search' => ['name', 'description'], 'with' => ['owner'], 'columns' => ['name', 'owner_id', 'status', 'created_at'], 'fields' => ['name' => 'text', 'owner_id' => 'relation:users', 'description' => 'textarea', 'status' => 'select:active,inactive']],
            'plans' => ['model' => Plan::class, 'title' => 'Plans', 'singular' => 'Plan', 'search' => ['name', 'description'], 'columns' => ['name', 'price', 'duration_days', 'is_active'], 'fields' => ['name' => 'text', 'price' => 'number', 'duration_days' => 'number', 'description' => 'textarea', 'is_active' => 'boolean']],
            'faqs' => ['model' => Faq::class, 'title' => 'FAQs', 'singular' => 'FAQ', 'search' => ['question', 'answer', 'category'], 'columns' => ['question', 'category', 'is_published', 'sort_order'], 'fields' => ['category' => 'text', 'question' => 'text', 'answer' => 'textarea', 'is_published' => 'boolean', 'sort_order' => 'number']],
            'geofences' => ['model' => Geofence::class, 'title' => 'Geofences', 'singular' => 'Geofence', 'search' => ['name'], 'columns' => ['name', 'shape', 'radius_meters', 'is_active'], 'fields' => ['name' => 'text', 'shape' => 'select:circle,polygon', 'latitude' => 'number', 'longitude' => 'number', 'radius_meters' => 'number', 'notify_entry' => 'boolean', 'notify_exit' => 'boolean', 'is_active' => 'boolean']],
            default => abort(404),
        };
    }

    private function rules(string $module, ?Model $record = null): array
    {
        return match ($module) {
            'users' => ['name' => ['required', 'string', 'max:255'], 'mobile' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($record)], 'email' => ['required', new SafeEmail, Rule::unique('users')->ignore($record)], 'password' => [$record ? 'nullable' : 'required', 'min:8'], 'profile_image' => ['nullable', 'image', 'max:2048'], 'gender' => ['nullable', Rule::in(['male', 'female', 'other'])], 'date_of_birth' => ['nullable', 'date', 'before:today'], 'status' => ['required', Rule::in(['active', 'suspended'])]],
            'devices' => ['name' => 'required|string|max:255', 'imei' => ['required', 'string', 'max:32', Rule::unique('devices')->ignore($record)], 'device_type' => 'required|string|max:50', 'model' => 'nullable|string|max:255', 'firmware_version' => 'nullable|string|max:100', 'sim_number' => 'nullable|string|max:30', 'user_id' => 'nullable|exists:users,id', 'is_enabled' => 'required|boolean'],
            'groups' => ['name' => 'required|string|max:255', 'owner_id' => 'required|exists:users,id', 'description' => 'nullable|string|max:2000', 'status' => ['required', Rule::in(['active', 'inactive'])]],
            'plans' => ['name' => ['required', 'string', Rule::unique('plans')->ignore($record)], 'price' => 'required|numeric|min:0', 'duration_days' => 'required|integer|min:1', 'description' => 'nullable|string', 'is_active' => 'required|boolean'],
            'faqs' => ['category' => 'nullable|string|max:100', 'question' => 'required|string|max:255', 'answer' => 'required|string', 'is_published' => 'required|boolean', 'sort_order' => 'required|integer|min:0'],
            'geofences' => ['name' => 'required|string|max:255', 'shape' => ['required', Rule::in(['circle', 'polygon'])], 'latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180', 'radius_meters' => 'nullable|required_if:shape,circle|integer|min:10', 'notify_entry' => 'required|boolean', 'notify_exit' => 'required|boolean', 'is_active' => 'required|boolean'],
            default => [],
        };
    }

    private function options(string $module): array
    {
        return in_array($module, ['devices', 'groups'], true) ? ['users' => User::orderBy('name')->pluck('name', 'id')] : [];
    }

    private function authorizePermission(string $module, string $action): void
    {
        abort_unless(auth()->user()->can("{$module}.{$action}"), 403);
    }
}
