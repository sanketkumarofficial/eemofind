<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPurchased
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $payload = [])
    {
    }
}