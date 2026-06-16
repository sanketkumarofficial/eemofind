<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushToken extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }
}
