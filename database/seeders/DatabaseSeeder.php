<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['users', 'devices', 'groups', 'plans', 'subscriptions', 'payments', 'tickets', 'faqs', 'geofences', 'reports', 'settings', 'sos'];
        $permissions = collect($modules)->flatMap(fn ($module) => collect(['view', 'create', 'update', 'delete'])->map(fn ($action) => "{$module}.{$action}"))->merge(['tickets.reply', 'reports.export'])->unique();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'Super Admin' => $permissions,
            'Operations Admin' => $permissions->filter(fn ($p) => str_starts_with($p, 'users.') || str_starts_with($p, 'devices.') || str_starts_with($p, 'groups.') || str_starts_with($p, 'geofences.') || str_starts_with($p, 'sos.')),
            'Finance Admin' => $permissions->filter(fn ($p) => str_starts_with($p, 'plans.') || str_starts_with($p, 'subscriptions.') || str_starts_with($p, 'payments.') || str_starts_with($p, 'reports.')),
            'Support Admin' => $permissions->filter(fn ($p) => str_starts_with($p, 'tickets.') || str_starts_with($p, 'faqs.') || $p === 'users.view'),
            'User' => collect(),
        ];
        foreach ($roles as $name => $allowed) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])->syncPermissions($allowed);
        }

        $admin = User::updateOrCreate(['email' => env('ADMIN_EMAIL', 'admin@eemofind.com')], ['name' => 'Eemo Super Admin', 'mobile' => '9999999999', 'password' => Hash::make(env('ADMIN_PASSWORD', 'Eemo@12345')), 'status' => 'active', 'referral_code' => Str::upper(Str::random(10))]);
        $admin->syncRoles(['Super Admin']);

        foreach ([
            ['Starter', 199, 30, ['1 group', '2 devices', 'Live tracking']],
            ['Family', 499, 90, ['5 groups', '10 devices', 'SOS and route playback']],
            ['Enterprise', 1999, 365, ['Unlimited groups', '100 devices', 'Priority support']],
        ] as [$name, $price, $days, $features]) {
            Plan::updateOrCreate(['name' => $name], ['price' => $price, 'duration_days' => $days, 'description' => "{$name} tracking subscription", 'features' => $features, 'is_active' => true]);
        }

        foreach (['app_name' => 'Eemo Find', 'heartbeat_interval' => '5', 'offline_timeout' => '10', 'pairing_code_length' => '8', 'theme_default' => 'light'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['group' => 'app', 'value' => $value, 'type' => is_numeric($value) ? 'integer' : 'string']);
        }
        Faq::updateOrCreate(['question' => 'How does device pairing work?'], ['category' => 'Device', 'answer' => 'Enter the active single-use pairing code in the mobile app. Membership is added immediately and a new code is generated.', 'is_published' => true]);
    }
}
