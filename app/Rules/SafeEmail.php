<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/[\x00-\x1F\x7F]/', $value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $fail('The :attribute must be a valid email address.');
        }
    }
}
