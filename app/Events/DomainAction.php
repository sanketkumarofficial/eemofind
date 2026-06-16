<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DomainAction
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $recipient, public string $type, public string $title, public string $message, public array $payload = []) {}
}
