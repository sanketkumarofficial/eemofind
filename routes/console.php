<?php

use App\Services\SubscriptionService;
use App\Services\TrackingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(TrackingService::class)->markOfflineDevices())->name('devices:mark-offline')->everyFiveMinutes()->withoutOverlapping();
Schedule::call(fn () => app(SubscriptionService::class)->expireDue())->name('subscriptions:expire')->dailyAt('00:10')->withoutOverlapping();
