<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function view(User $user, Device $device): bool
    {
        return $user->id === $device->user_id || $user->can('devices.view');
    }

    public function update(User $user, Device $device): bool
    {
        return $user->id === $device->user_id || $user->can('devices.update');
    }
}
