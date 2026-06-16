<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Geofence extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['polygon' => 'array', 'notify_entry' => 'boolean', 'notify_exit' => 'boolean', 'is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'geofence_users')->withTimestamps();
    }
}
