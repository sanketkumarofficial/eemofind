<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }

    public function getDecodedValueAttribute(): mixed
    {
        $value = $this->is_encrypted && $this->value ? Crypt::decryptString($this->value) : $this->value;

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL), 'integer' => (int) $value, 'json' => json_decode($value ?? '[]', true), default => $value
        };
    }
}
