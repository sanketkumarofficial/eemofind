<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['rewarded_at' => 'datetime', 'reward_amount' => 'decimal:2'];
    }
}
