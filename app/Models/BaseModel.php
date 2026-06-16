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