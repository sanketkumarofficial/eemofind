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