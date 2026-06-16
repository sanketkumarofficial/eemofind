<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_online' => 'boolean', 'last_seen_at' => 'datetime', 'location_recorded_at' => 'datetime'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
