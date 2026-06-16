<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $payload = [])
    {
    }

    public function handle(): void
    {
        Log::info('GenerateReportJob processed', ['payload' => $this->payload]);
    }
}