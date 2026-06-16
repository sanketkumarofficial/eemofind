<?php

namespace App\Policies;

use App\Models\TrackingGroup;
use App\Models\User;

class TrackingGroupPolicy
{
    public function view(User $user, TrackingGroup $group): bool
    {
        return $group->owner_id === $user->id || $group->members()->where('users.id', $user->id)->exists() || $user->can('groups.view');
    }

    public function update(User $user, TrackingGroup $group): bool
    {
        return $group->owner_id === $user->id || $group->members()->where('users.id', $user->id)->wherePivot('role', 'admin')->exists() || $user->can('groups.update');
    }

    public function delete(User $user, TrackingGroup $group): bool
    {
        return $group->owner_id === $user->id || $user->can('groups.delete');
    }
}
