<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SubscriptionExpiryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $payload = [])
    {
    }

    public function handle(): void
    {
        Log::info('SubscriptionExpiryJob processed', ['payload' => $this->payload]);
    }
}