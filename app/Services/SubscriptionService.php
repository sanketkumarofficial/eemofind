<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function create(User $user, Plan $plan, ?float $amount = null, ?Subscription $renewing = null): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $amount, $renewing) {
            $start = $renewing && $renewing->end_date->isFuture() ? $renewing->end_date->copy()->addDay() : today();

            return Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'amount' => $amount ?? $plan->price, 'start_date' => $start, 'end_date' => $start->copy()->addDays($plan->duration_days - 1), 'status' => 'pending']);
        });
    }

    public function activate(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            Subscription::where('user_id', $subscription->user_id)->where('id', '!=', $subscription->id)->where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $subscription->update(['status' => 'active']);

            return $subscription->fresh();
        });
    }

    public function expireDue(): int
    {
        return Subscription::where('status', 'active')->whereDate('end_date', '<', today())->update(['status' => 'expired']);
    }

    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return $subscription->fresh();
    }
}
