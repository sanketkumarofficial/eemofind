<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrackingGroup extends Model
{
    use SoftDeletes;

    protected $table = 'groups';

    protected $guarded = [];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')->withPivot('role', 'joined_at')->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    public function pairingCodes(): HasMany
    {
        return $this->hasMany(PairingCode::class, 'group_id');
    }
}
