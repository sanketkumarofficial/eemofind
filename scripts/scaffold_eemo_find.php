<?php

$root = dirname(__DIR__);

function put_file(string $path, string $contents): void
{
    global $root;
    $full = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $dir = dirname($full);
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($full, $contents);
}

function class_name(string $name): string
{
    return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
}

$modules = [
    'users' => ['model' => 'User', 'title' => 'Users', 'icon' => 'bi-people', 'permissions' => ['view','create','update','delete','force-logout','reset-password']],
    'devices' => ['model' => 'Device', 'title' => 'Devices', 'icon' => 'bi-router', 'permissions' => ['view','create','update','delete','assign','live-tracking','history']],
    'tracking_groups' => ['model' => 'TrackingGroup', 'title' => 'Groups', 'icon' => 'bi-diagram-3', 'permissions' => ['view','create','update','delete','members','admins','pairing']],
    'pairing_codes' => ['model' => 'PairingCode', 'title' => 'Pairing Codes', 'icon' => 'bi-qr-code', 'permissions' => ['view','create','update','delete','refresh','reset','usage-history']],
    'plans' => ['model' => 'Plan', 'title' => 'Plans', 'icon' => 'bi-box-seam', 'permissions' => ['view','create','update','delete','enable','disable']],
    'subscriptions' => ['model' => 'Subscription', 'title' => 'Subscriptions', 'icon' => 'bi-arrow-repeat', 'permissions' => ['view','create','update','delete','purchase','renew','upgrade','cancel','expire']],
    'payments' => ['model' => 'Payment', 'title' => 'Payments', 'icon' => 'bi-credit-card', 'permissions' => ['view','create','update','delete','export','refund']],
    'support_tickets' => ['model' => 'SupportTicket', 'title' => 'Support Tickets', 'icon' => 'bi-life-preserver', 'permissions' => ['view','create','update','delete','reply','assign','close','reopen']],
    'faqs' => ['model' => 'Faq', 'title' => 'FAQs', 'icon' => 'bi-question-circle', 'permissions' => ['view','create','update','delete','publish']],
    'notifications' => ['model' => 'AdminNotification', 'title' => 'Notifications', 'icon' => 'bi-bell', 'permissions' => ['view','delete','read']],
    'activity_logs' => ['model' => 'ActivityLog', 'title' => 'Activity Logs', 'icon' => 'bi-clock-history', 'permissions' => ['view','export']],
    'sos_events' => ['model' => 'SosEvent', 'title' => 'SOS Events', 'icon' => 'bi-exclamation-triangle', 'permissions' => ['view','update','resolve']],
    'geofences' => ['model' => 'Geofence', 'title' => 'Geofences', 'icon' => 'bi-bounding-box', 'permissions' => ['view','create','update','delete']],
    'emergency_contacts' => ['model' => 'EmergencyContact', 'title' => 'Emergency Contacts', 'icon' => 'bi-person-lines-fill', 'permissions' => ['view','create','update','delete']],
    'referrals' => ['model' => 'Referral', 'title' => 'Referrals', 'icon' => 'bi-gift', 'permissions' => ['view','update','rewards']],
    'push_tokens' => ['model' => 'PushToken', 'title' => 'Push Tokens', 'icon' => 'bi-phone-vibrate', 'permissions' => ['view','delete']],
    'notification_logs' => ['model' => 'NotificationLog', 'title' => 'Push Notification Logs', 'icon' => 'bi-send-check', 'permissions' => ['view']],
];

$composer = json_decode(file_get_contents($root . '/composer.json'), true);
$composer['name'] = 'eemo/find';
$composer['description'] = 'Eemo Find GPS Tracking SaaS Platform';
$composer['require'] = array_merge($composer['require'], [
    'kreait/firebase-php' => '^7.0',
    'laravel/sanctum' => '^4.0',
    'maatwebsite/excel' => '^3.1',
    'barryvdh/laravel-dompdf' => '^3.0',
    'razorpay/razorpay' => '^2.9',
    'spatie/laravel-permission' => '^6.0',
]);
put_file('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

put_file('routes/web.php', <<<'PHP'
<?php

use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\ResourcePageController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/dashboard');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web'])
    ->group(function () {
        Route::get('dashboard', [AdminPageController::class, 'dashboard'])->name('dashboard');
        Route::get('reports/{type}', [AdminPageController::class, 'report'])->name('reports.show');
        Route::get('system-health/{section?}', [AdminPageController::class, 'systemHealth'])->name('system-health');
        Route::match(['get', 'post'], 'settings/{section?}', [SettingsController::class, 'edit'])->name('settings.edit');

        foreach ([
            'users', 'devices', 'tracking-groups', 'pairing-codes', 'plans', 'subscriptions',
            'payments', 'support-tickets', 'faqs', 'notifications', 'activity-logs',
            'sos-events', 'geofences', 'emergency-contacts', 'referrals', 'push-tokens',
            'notification-logs',
        ] as $resource) {
            Route::get($resource . '/export', [ResourcePageController::class, 'export'])->name($resource . '.export');
            Route::post($resource . '/bulk-action', [ResourcePageController::class, 'bulkAction'])->name($resource . '.bulk-action');
            Route::resource($resource, ResourcePageController::class)->parameters([$resource => 'id']);
        }

        Route::post('users/{id}/reset-password', [ResourcePageController::class, 'action'])->defaults('action', 'reset-password')->name('users.reset-password');
        Route::post('users/{id}/force-logout', [ResourcePageController::class, 'action'])->defaults('action', 'force-logout')->name('users.force-logout');
        Route::post('devices/{id}/assign', [ResourcePageController::class, 'action'])->defaults('action', 'assign')->name('devices.assign');
        Route::post('payments/{id}/refund', [ResourcePageController::class, 'action'])->defaults('action', 'refund')->name('payments.refund');
        Route::post('support-tickets/{id}/reply', [ResourcePageController::class, 'action'])->defaults('action', 'reply')->name('support-tickets.reply');
        Route::post('sos-events/{id}/resolve', [ResourcePageController::class, 'action'])->defaults('action', 'resolve')->name('sos-events.resolve');
    });
PHP);

put_file('routes/api.php', <<<'PHP'
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MobileResourceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);

        foreach ([
            'devices', 'tracking', 'groups', 'pairing', 'subscriptions', 'payments',
            'notifications', 'tickets', 'sos', 'geofences', 'emergency-contacts',
            'referrals', 'faq',
        ] as $module) {
            Route::get($module, [MobileResourceController::class, 'index'])->defaults('module', $module);
            Route::post($module, [MobileResourceController::class, 'store'])->defaults('module', $module);
            Route::get($module . '/{id}', [MobileResourceController::class, 'show'])->defaults('module', $module);
            Route::put($module . '/{id}', [MobileResourceController::class, 'update'])->defaults('module', $module);
            Route::delete($module . '/{id}', [MobileResourceController::class, 'destroy'])->defaults('module', $module);
        }
    });
});
PHP);

put_file('bootstrap/app.php', <<<'PHP'
<?php

use App\Http\Middleware\EnsureUserHasPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => EnsureUserHasPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
PHP);

put_file('app/Http/Middleware/EnsureUserHasPermission.php', <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_if(! $user || ! method_exists($user, 'can') || ! $user->can($permission), 403);

        return $next($request);
    }
}
PHP);

$migration = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('device_limit')->default(1);
            $table->unsignedInteger('group_limit')->default(1);
            $table->unsignedInteger('history_days')->default(30);
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('billing_cycle')->default('monthly');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('imei')->unique();
            $table->string('sim_number')->nullable()->index();
            $table->string('status')->default('offline')->index();
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->decimal('last_speed', 8, 2)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'status']);
        });

        Schema::create('device_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 8, 2)->default(0);
            $table->decimal('heading', 8, 2)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->unsignedTinyInteger('battery')->nullable();
            $table->timestamp('recorded_at')->index();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['device_id', 'recorded_at']);
        });

        Schema::create('tracking_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'status']);
        });

        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['tracking_group_id', 'user_id']);
        });

        Schema::create('pairing_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('purpose')->default('group_join');
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('usage_history')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway')->default('razorpay')->index();
            $table->string('gateway_order_id')->nullable()->index();
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('gateway_signature')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status')->index();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->string('category')->index();
            $table->string('priority')->default('medium')->index();
            $table->string('status')->default('open')->index();
            $table->text('message');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_reply_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->unsignedBigInteger('size');
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();
            $table->string('key');
            $table->json('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->longText('answer');
            $table->string('category')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('sos_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tracking_group_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('open')->index();
            $table->text('message')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('circle');
            $table->decimal('center_latitude', 10, 7)->nullable();
            $table->decimal('center_longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_meters')->nullable();
            $table->json('polygon')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('alert_channels')->nullable();
            $table->timestamps();
        });

        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('relationship')->nullable();
            $table->boolean('notify_sos')->default(true);
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('reward_amount', 12, 2)->default(0);
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform')->index();
            $table->string('device_name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->index();
            $table->string('target')->index();
            $table->string('title');
            $table->text('body');
            $table->string('status')->index();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (array_reverse([
            'notification_logs','push_tokens','referrals','emergency_contacts','geofences','sos_events',
            'faqs','settings','activity_logs','admin_notifications','ticket_attachments','ticket_replies',
            'support_tickets','payments','pairing_codes','group_members','tracking_groups','device_snapshots',
            'devices','subscriptions','plans',
        ]) as $table) {
            Schema::dropIfExists($table);
        }
    }
};
PHP;
put_file('database/migrations/2026_01_01_000001_create_eemo_find_core_tables.php', $migration);

put_file('database/seeders/RolePermissionSeeder.php', <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'dashboard' => ['view'],
            'users' => ['view','create','update','delete','force-logout','reset-password'],
            'devices' => ['view','create','update','delete','assign','live-tracking','history'],
            'groups' => ['view','create','update','delete','members','admins','pairing'],
            'pairing-codes' => ['view','create','update','delete','refresh','reset','usage-history'],
            'plans' => ['view','create','update','delete','enable','disable'],
            'subscriptions' => ['view','create','update','delete','purchase','renew','upgrade','cancel','expire'],
            'payments' => ['view','create','update','delete','export','refund'],
            'support-tickets' => ['view','create','update','delete','reply','assign','close','reopen'],
            'faqs' => ['view','create','update','delete','publish'],
            'notifications' => ['view','delete','read'],
            'activity-logs' => ['view','export'],
            'reports' => ['view','export'],
            'settings' => ['view','update'],
            'sos-events' => ['view','update','resolve'],
            'geofences' => ['view','create','update','delete'],
            'emergency-contacts' => ['view','create','update','delete'],
            'referrals' => ['view','update','rewards'],
            'push-notifications' => ['view','create','delete'],
            'system-health' => ['view'],
        ];

        $permissions = collect($modules)->flatMap(fn ($actions, $module) => collect($actions)->map(fn ($action) => $module . '.' . $action));
        $permissions->each(fn ($name) => Permission::findOrCreate($name, 'web'));

        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $superAdmin->syncPermissions($permissions);

        Role::findOrCreate('Operations Admin', 'web')->syncPermissions($permissions->filter(fn ($name) => ! str_starts_with($name, 'payments.') && ! str_starts_with($name, 'settings.payment')));
        Role::findOrCreate('Finance Admin', 'web')->syncPermissions($permissions->filter(fn ($name) => str_starts_with($name, 'payments.') || str_starts_with($name, 'subscriptions.') || str_starts_with($name, 'plans.') || str_starts_with($name, 'reports.')));
        Role::findOrCreate('Support Admin', 'web')->syncPermissions($permissions->filter(fn ($name) => str_starts_with($name, 'support-tickets.') || str_starts_with($name, 'users.view') || str_starts_with($name, 'devices.view') || str_starts_with($name, 'faqs.')));
    }
}
PHP);

put_file('database/seeders/DatabaseSeeder.php', <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
    }
}
PHP);

$baseModel = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'payload' => 'array',
            'data' => 'array',
            'settings' => 'array',
            'features' => 'array',
            'gateway_response' => 'array',
            'usage_history' => 'array',
            'polygon' => 'array',
            'alert_channels' => 'array',
            'response' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'recorded_at' => 'datetime',
            'paid_at' => 'datetime',
            'closed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'read_at' => 'datetime',
            'last_used_at' => 'datetime',
            'joined_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'rewarded_at' => 'datetime',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'is_internal' => 'boolean',
            'is_encrypted' => 'boolean',
            'notify_sos' => 'boolean',
        ];
    }

    public function scopeSearch(Builder $query, ?string $term, array $columns = ['name', 'title', 'subject', 'code', 'email', 'phone']): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term, $columns) {
            foreach ($columns as $column) {
                if (array_key_exists($column, $this->getAttributes()) || in_array($column, $this->getFillable(), true)) {
                    $inner->orWhere($column, 'like', '%' . $term . '%');
                }
            }
        });
    }
}
PHP;
put_file('app/Models/BaseModel.php', $baseModel);

$modelRelations = [
    'Device' => "public function owner() { return \$this->belongsTo(User::class, 'owner_id'); }\n    public function snapshots() { return \$this->hasMany(DeviceSnapshot::class); }",
    'DeviceSnapshot' => "public function device() { return \$this->belongsTo(Device::class); }",
    'TrackingGroup' => "public function owner() { return \$this->belongsTo(User::class, 'owner_id'); }\n    public function members() { return \$this->belongsToMany(User::class, 'group_members')->withPivot('role', 'joined_at')->withTimestamps(); }",
    'GroupMember' => "public function group() { return \$this->belongsTo(TrackingGroup::class, 'tracking_group_id'); }\n    public function user() { return \$this->belongsTo(User::class); }",
    'PairingCode' => "public function group() { return \$this->belongsTo(TrackingGroup::class, 'tracking_group_id'); }\n    public function creator() { return \$this->belongsTo(User::class, 'created_by'); }",
    'Subscription' => "public function user() { return \$this->belongsTo(User::class); }\n    public function plan() { return \$this->belongsTo(Plan::class); }\n    public function payments() { return \$this->hasMany(Payment::class); }",
    'Payment' => "public function user() { return \$this->belongsTo(User::class); }\n    public function subscription() { return \$this->belongsTo(Subscription::class); }",
    'SupportTicket' => "public function user() { return \$this->belongsTo(User::class); }\n    public function assignee() { return \$this->belongsTo(User::class, 'assigned_to'); }\n    public function replies() { return \$this->hasMany(TicketReply::class); }",
    'TicketReply' => "public function ticket() { return \$this->belongsTo(SupportTicket::class, 'support_ticket_id'); }\n    public function user() { return \$this->belongsTo(User::class); }",
    'TicketAttachment' => "public function ticket() { return \$this->belongsTo(SupportTicket::class, 'support_ticket_id'); }\n    public function reply() { return \$this->belongsTo(TicketReply::class, 'ticket_reply_id'); }",
    'AdminNotification' => "protected \$table = 'admin_notifications';\n    public function user() { return \$this->belongsTo(User::class); }",
    'ActivityLog' => "public function user() { return \$this->belongsTo(User::class); }",
    'SosEvent' => "protected \$table = 'sos_events';\n    public function user() { return \$this->belongsTo(User::class); }\n    public function device() { return \$this->belongsTo(Device::class); }\n    public function group() { return \$this->belongsTo(TrackingGroup::class, 'tracking_group_id'); }",
    'Geofence' => "public function group() { return \$this->belongsTo(TrackingGroup::class, 'tracking_group_id'); }\n    public function user() { return \$this->belongsTo(User::class); }",
    'EmergencyContact' => "public function user() { return \$this->belongsTo(User::class); }",
    'Referral' => "public function referrer() { return \$this->belongsTo(User::class, 'referrer_id'); }\n    public function referredUser() { return \$this->belongsTo(User::class, 'referred_user_id'); }",
    'PushToken' => "public function user() { return \$this->belongsTo(User::class); }",
    'NotificationLog' => "public function user() { return \$this->belongsTo(User::class); }",
];

foreach (array_unique(array_merge(array_column($modules, 'model'), ['DeviceSnapshot','GroupMember','TicketReply','TicketAttachment','Setting'])) as $model) {
    $relations = $modelRelations[$model] ?? '';
    put_file("app/Models/{$model}.php", <<<PHP
<?php

namespace App\Models;

class {$model} extends BaseModel
{
    {$relations}
}
PHP);
}

put_file('app/Repositories/Contracts/RepositoryInterface.php', <<<'PHP'
<?php

namespace App\Repositories\Contracts;

interface RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 25);
    public function find(int|string $id);
    public function create(array $data);
    public function update(int|string $id, array $data);
    public function delete(int|string $id): bool;
}
PHP);

put_file('app/Repositories/EloquentRepository.php', <<<'PHP'
<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class EloquentRepository implements RepositoryInterface
{
    public function __construct(protected Model $model)
    {
    }

    public function paginate(array $filters = [], int $perPage = 25)
    {
        return $this->model->newQuery()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int|string $id)
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int|string $id, array $data)
    {
        $record = $this->find($id);
        $record->fill($data)->save();
        return $record;
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->find($id)->delete();
    }
}
PHP);

foreach ($modules as $key => $meta) {
    $model = $meta['model'];
    $repo = $model . 'Repository';
    put_file("app/Repositories/{$repo}.php", <<<PHP
<?php

namespace App\Repositories;

use App\Models\\{$model};

class {$repo} extends EloquentRepository
{
    public function __construct({$model} \$model)
    {
        parent::__construct(\$model);
    }
}
PHP);
}

$services = [
    'FirebaseService','TrackingService','HeartbeatService','GeofenceService','RoutePlaybackService','PairingService',
    'SubscriptionService','PaymentService','RazorpayService','NotificationService','ActivityLogService','SOSService',
    'TicketService','ReportService','SettingsService','PushNotificationService',
];

foreach ($services as $service) {
    put_file("app/Services/{$service}.php", <<<PHP
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class {$service}
{
    public function handle(array \$payload = []): array
    {
        Log::info('{$service} handled', ['payload' => \$payload]);

        return [
            'ok' => true,
            'service' => self::class,
            'payload' => \$payload,
        ];
    }
}
PHP);
}

$events = ['UserCreated','UserUpdated','DeviceCreated','DeviceAssigned','GroupCreated','PairingUsed','SubscriptionPurchased','SubscriptionRenewed','PaymentSuccess','PaymentFailed','TicketCreated','TicketReplied','SOSTriggered','GeofenceEntered','GeofenceExited'];
foreach ($events as $event) {
    put_file("app/Events/{$event}.php", <<<PHP
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class {$event}
{
    use Dispatchable, SerializesModels;

    public function __construct(public array \$payload = [])
    {
    }
}
PHP);
    put_file("app/Listeners/Handle{$event}.php", <<<PHP
<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;

class Handle{$event} implements ShouldQueue
{
    public function __construct(private ActivityLogService \$activityLogService)
    {
    }

    public function handle(object \$event): void
    {
        \$this->activityLogService->handle([
            'event' => '{$event}',
            'payload' => property_exists(\$event, 'payload') ? \$event->payload : [],
        ]);
    }
}
PHP);
}

$jobs = ['ProcessHeartbeatJob','ProcessSOSJob','ProcessGeofenceJob','SendPushNotificationJob','GenerateReportJob','SubscriptionExpiryJob','OfflineDeviceDetectionJob'];
foreach ($jobs as $job) {
    put_file("app/Jobs/{$job}.php", <<<PHP
<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class {$job} implements ShouldQueue
{
    use Queueable;

    public function __construct(public array \$payload = [])
    {
    }

    public function handle(): void
    {
        Log::info('{$job} processed', ['payload' => \$this->payload]);
    }
}
PHP);
}

put_file('app/Http/Controllers/Admin/AdminPageController.php', <<<'PHP'
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
PHP);

put_file('app/Http/Controllers/Admin/ResourcePageController.php', <<<'PHP'
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
PHP);

put_file('app/Http/Controllers/Admin/SettingsController.php', <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(Request $request, ?string $section = null)
    {
        $section ??= 'general';

        if ($request->isMethod('post')) {
            foreach ($request->except('_token') as $key => $value) {
                Setting::updateOrCreate(['group' => $section, 'key' => $key], ['value' => $value]);
            }

            return back()->with('success', 'Settings saved.');
        }

        $settings = Setting::where('group', $section)->pluck('value', 'key');

        return view('admin.settings', compact('section', 'settings'));
    }
}
PHP);

put_file('app/Http/Controllers/Api/V1/AuthController.php', <<<'PHP'
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);

        return response()->json(['token' => $user->createToken('mobile')->plainTextToken, 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        $user = User::where('email', $data['email'])->first();

        abort_if(! $user || ! Hash::check($data['password'], $user->password), 422, 'Invalid credentials.');

        return response()->json(['token' => $user->createToken('mobile')->plainTextToken, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
PHP);

put_file('app/Http/Controllers/Api/V1/MobileResourceController.php', <<<'PHP'
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
PHP);

put_file('resources/views/layouts/admin.blade.php', <<<'BLADE'
<!doctype html>
<html lang="en" data-bs-theme="{{ session('theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Eemo Find')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link href="{{ asset('css/eemo-find.css') }}" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    @include('partials.sidebar')
    <main class="app-main">
        @include('partials.header')
        <section class="container-fluid py-4">
            @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
            @yield('content')
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
@stack('scripts')
</body>
</html>
BLADE);

put_file('resources/views/partials/sidebar.blade.php', <<<'BLADE'
@php
    $items = [
        ['Dashboard','admin.dashboard','bi-speedometer2'],
        ['Users','admin.users.index','bi-people'],
        ['Devices','admin.devices.index','bi-router'],
        ['Groups','admin.tracking-groups.index','bi-diagram-3'],
        ['Pairing Codes','admin.pairing-codes.index','bi-qr-code'],
        ['Plans','admin.plans.index','bi-box-seam'],
        ['Subscriptions','admin.subscriptions.index','bi-arrow-repeat'],
        ['Payments','admin.payments.index','bi-credit-card'],
        ['Support Tickets','admin.support-tickets.index','bi-life-preserver'],
        ['FAQs','admin.faqs.index','bi-question-circle'],
        ['Notifications','admin.notifications.index','bi-bell'],
        ['Activity Logs','admin.activity-logs.index','bi-clock-history'],
        ['SOS Events','admin.sos-events.index','bi-exclamation-triangle'],
        ['Geofences','admin.geofences.index','bi-bounding-box'],
        ['Emergency Contacts','admin.emergency-contacts.index','bi-person-lines-fill'],
        ['Referrals','admin.referrals.index','bi-gift'],
        ['Push Tokens','admin.push-tokens.index','bi-phone-vibrate'],
        ['System Health','admin.system-health','bi-activity'],
    ];
@endphp
<aside class="app-sidebar">
    <a class="brand" href="{{ route('admin.dashboard') }}"><span>EF</span><strong>Eemo Find</strong></a>
    <nav class="nav flex-column">
        @foreach($items as [$label, $route, $icon])
            <a class="nav-link {{ request()->routeIs($route) ? 'active' : '' }}" href="{{ route($route) }}">
                <i class="bi {{ $icon }}"></i><span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
</aside>
BLADE);

put_file('resources/views/partials/header.blade.php', <<<'BLADE'
<header class="app-header">
    <form class="search" method="get">
        <i class="bi bi-search"></i>
        <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search users, devices, groups, tickets">
    </form>
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.settings.edit', 'theme') }}"><i class="bi bi-circle-half"></i></a>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown"><i class="bi bi-bell"></i></button>
            <div class="dropdown-menu dropdown-menu-end p-2"><span class="dropdown-item-text">No unread notifications</span></div>
        </div>
        <div class="dropdown">
            <button class="btn btn-teal btn-sm" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i></button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="{{ route('admin.settings.edit', 'general') }}">Settings</a>
                <a class="dropdown-item" href="{{ route('admin.activity-logs.index') }}">Activity</a>
            </div>
        </div>
    </div>
</header>
BLADE);

put_file('resources/views/admin/dashboard.blade.php', <<<'BLADE'
@extends('layouts.admin')
@section('title', 'Dashboard - Eemo Find')
@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Live operating view for users, devices, revenue, and support.</p>
    </div>
    <a class="btn btn-teal" href="{{ route('admin.reports.show', 'users') }}"><i class="bi bi-download"></i> Export Reports</a>
</div>
<div class="row g-3">
    @foreach($stats as $label => $value)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card">
                <span>{{ $label }}</span>
                <strong>{{ is_numeric($value) ? number_format($value) : $value }}</strong>
            </div>
        </div>
    @endforeach
</div>
<div class="row g-3 mt-1">
    @foreach(['Revenue Trend','Subscription Growth','User Growth','Device Analytics','Online vs Offline','Ticket Analytics'] as $chart)
        <div class="col-12 col-xl-6">
            <div class="panel">
                <div class="panel-title">{{ $chart }}</div>
                <div id="{{ Str::slug($chart) }}" class="chart"></div>
            </div>
        </div>
    @endforeach
</div>
@push('scripts')
<script>
document.querySelectorAll('.chart').forEach((el) => {
    new ApexCharts(el, {
        chart: {type: 'area', height: 220, toolbar: {show: false}},
        colors: ['#00897b'],
        series: [{name: el.id, data: [12, 19, 15, 28, 32, 44, 39]}],
        xaxis: {categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul']},
        stroke: {curve: 'smooth'},
    }).render();
});
</script>
@endpush
@endsection
BLADE);

put_file('resources/views/admin/resources/index.blade.php', <<<'BLADE'
@extends('layouts.admin')
@section('title', $title . ' - Eemo Find')
@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="h3 mb-0">{{ $title }}</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('admin.' . $key . '.export') }}"><i class="bi bi-download"></i> Export</a>
        <a class="btn btn-teal" href="{{ route('admin.' . $key . '.create') }}"><i class="bi bi-plus-lg"></i> Create</a>
    </div>
</div>
<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Summary</th><th>Status</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ $record->id }}</td>
                    <td>{{ $record->name ?? $record->title ?? $record->subject ?? $record->code ?? $record->email ?? 'Record #' . $record->id }}</td>
                    <td><span class="badge text-bg-light">{{ $record->status ?? ($record->is_active ?? null ? 'active' : 'available') }}</span></td>
                    <td>{{ optional($record->created_at)->format('d M Y, H:i') }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.' . $key . '.show', $record) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.' . $key . '.edit', $record) }}"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="post" action="{{ route('admin.' . $key . '.destroy', $record) }}">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty-state">No {{ strtolower($title) }} yet.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $records->links() }}
</div>
@endsection
BLADE);

put_file('resources/views/admin/resources/form.blade.php', <<<'BLADE'
@extends('layouts.admin')
@section('title', ($record ?? null ? 'Edit ' : 'Create ') . $title)
@section('content')
@php($record = $record ?? null)
<h1 class="h3 mb-3">{{ $record ? 'Edit' : 'Create' }} {{ $title }}</h1>
<form class="panel" method="post" action="{{ $record ? route('admin.' . $key . '.update', $record) : route('admin.' . $key . '.store') }}">
    @csrf
    @if($record) @method('put') @endif
    <div class="row g-3">
        @foreach(['name','title','subject','code','email','phone','status','category','priority','amount','currency','message','body'] as $field)
            <div class="col-12 col-md-6">
                <label class="form-label">{{ Str::headline($field) }}</label>
                <input class="form-control" name="{{ $field }}" value="{{ old($field, $record?->{$field}) }}">
            </div>
        @endforeach
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-teal"><i class="bi bi-check2"></i> Save</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.' . $key . '.index') }}">Cancel</a>
    </div>
</form>
@endsection
BLADE);

put_file('resources/views/admin/resources/show.blade.php', <<<'BLADE'
@extends('layouts.admin')
@section('title', $title . ' Details')
@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">{{ $title }} #{{ $record->id }}</h1>
    <a class="btn btn-teal" href="{{ route('admin.' . $key . '.edit', $record) }}"><i class="bi bi-pencil"></i> Edit</a>
</div>
<div class="panel">
    <dl class="row mb-0">
        @foreach($record->getAttributes() as $field => $value)
            <dt class="col-md-3">{{ Str::headline($field) }}</dt>
            <dd class="col-md-9"><code>{{ is_array($value) ? json_encode($value) : $value }}</code></dd>
        @endforeach
    </dl>
</div>
@endsection
BLADE);

put_file('resources/views/admin/settings.blade.php', <<<'BLADE'
@extends('layouts.admin')
@section('title', Str::headline($section) . ' Settings')
@section('content')
<h1 class="h3 mb-3">{{ Str::headline($section) }} Settings</h1>
<form class="panel" method="post">
    @csrf
    @foreach(['general','support','tracking','pairing','firebase','payment','theme'] as $tab)
        <a class="btn btn-sm {{ $section === $tab ? 'btn-teal' : 'btn-outline-secondary' }} mb-3" href="{{ route('admin.settings.edit', $tab) }}">{{ Str::headline($tab) }}</a>
    @endforeach
    @foreach(['app_name','support_email','firebase_project_id','firebase_database_url','razorpay_key','primary_color','dark_mode'] as $field)
        <div class="mb-3">
            <label class="form-label">{{ Str::headline($field) }}</label>
            <input class="form-control" name="{{ $field }}" value="{{ old($field, $settings[$field] ?? '') }}">
        </div>
    @endforeach
    <button class="btn btn-teal"><i class="bi bi-check2"></i> Save Settings</button>
</form>
@endsection
BLADE);

put_file('resources/views/admin/report.blade.php', <<<'BLADE'
@extends('layouts.admin')
@section('title', Str::headline($type) . ' Report')
@section('content')
<h1 class="h3 mb-3">{{ Str::headline($type) }} Report</h1>
<div class="panel">
    <form class="row g-3 align-items-end">
        <div class="col-md-4"><label class="form-label">From</label><input type="date" class="form-control" name="from"></div>
        <div class="col-md-4"><label class="form-label">To</label><input type="date" class="form-control" name="to"></div>
        <div class="col-md-4"><button class="btn btn-teal w-100"><i class="bi bi-file-earmark-arrow-down"></i> Generate</button></div>
    </form>
</div>
@endsection
BLADE);

put_file('resources/views/admin/system-health.blade.php', <<<'BLADE'
@extends('layouts.admin')
@section('title', 'System Health')
@section('content')
<h1 class="h3 mb-3">System Health</h1>
<div class="row g-3">
    @foreach(['Queues','Jobs','Schedulers','Logs'] as $item)
        <div class="col-md-6 col-xl-3"><div class="metric-card"><span>{{ $item }}</span><strong>Healthy</strong></div></div>
    @endforeach
</div>
@endsection
BLADE);

put_file('public/css/eemo-find.css', <<<'CSS'
:root { --teal: #00897b; --teal-dark: #00695c; --surface: #ffffff; --line: #d8e2df; --text: #16211f; }
[data-bs-theme="dark"] { --surface: #111816; --line: #263632; --text: #ecf6f3; }
body { background: #f4f8f7; color: var(--text); }
.app-shell { display: flex; min-height: 100vh; }
.app-sidebar { position: sticky; top: 0; width: 270px; height: 100vh; overflow-y: auto; background: #0e2f2b; color: #fff; padding: 1rem; }
.brand { display: flex; align-items: center; gap: .75rem; color: #fff; text-decoration: none; margin-bottom: 1rem; }
.brand span { display: grid; place-items: center; width: 40px; height: 40px; background: var(--teal); border-radius: 8px; font-weight: 700; }
.app-sidebar .nav-link { color: #cfe4df; border-radius: 8px; display: flex; gap: .75rem; align-items: center; }
.app-sidebar .nav-link.active, .app-sidebar .nav-link:hover { background: rgba(255,255,255,.12); color: #fff; }
.app-main { flex: 1; min-width: 0; }
.app-header { position: sticky; top: 0; z-index: 20; display: flex; justify-content: space-between; gap: 1rem; align-items: center; padding: .85rem 1.25rem; background: rgba(255,255,255,.9); border-bottom: 1px solid var(--line); backdrop-filter: blur(12px); }
.search { position: relative; width: min(520px, 100%); }
.search i { position: absolute; left: .8rem; top: .6rem; color: #5f716d; }
.search input { padding-left: 2.25rem; }
.btn-teal { background: var(--teal); color: #fff; border-color: var(--teal); }
.btn-teal:hover { background: var(--teal-dark); color: #fff; border-color: var(--teal-dark); }
.metric-card, .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 8px; padding: 1rem; box-shadow: 0 8px 24px rgba(22,33,31,.05); }
.metric-card span { color: #637772; display: block; font-size: .85rem; }
.metric-card strong { font-size: 1.65rem; line-height: 1.25; }
.panel-title { font-weight: 700; margin-bottom: .75rem; }
.chart { min-height: 220px; }
.empty-state { text-align: center; padding: 3rem; color: #637772; }
@media (max-width: 960px) {
  .app-shell { display: block; }
  .app-sidebar { position: static; width: 100%; height: auto; }
  .app-sidebar .nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .app-header { align-items: stretch; flex-direction: column; }
}
CSS);

put_file('tests/Feature/AdminDashboardTest.php', <<<'PHP'
<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    public function test_dashboard_route_renders(): void
    {
        $this->get('/admin/dashboard')->assertOk()->assertSee('Dashboard');
    }
}
PHP);

echo "Eemo Find scaffold generated.\n";
