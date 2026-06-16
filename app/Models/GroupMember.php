<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMember extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TrackingGroup::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
