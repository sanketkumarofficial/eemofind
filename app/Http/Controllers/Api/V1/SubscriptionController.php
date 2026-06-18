<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function plans()
    {
        return response()->json(
            Plan::where('is_active', 1)->get()
        );
    }

    public function subscriptions(Request $request)
    {
        return response()->json(
            Subscription::with('plan')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get()
        );
    }

    public function purchase(Request $request, Plan $plan)
    {
        $subscription = Subscription::create([
            'user_id'    => $request->user()->id,
            'plan_id'    => $plan->id,
            'amount'     => $plan->price,
            'status'     => 'pending',
            'start_date' => now(),
            'end_date'   => now()->addMonth(),
        ]);

        $payment = Payment::create([
            'user_id'         => $request->user()->id,
            'subscription_id' => $subscription->id,
            'amount'          => $plan->price,
            'status'          => 'pending',
            'payment_method'  => 'razorpay',
        ]);

        return response()->json([
            'success' => true,
            'subscription' => $subscription,
            'payment' => $payment,
        ]);
    }

    public function verifyPayment(Request $request, Payment $payment)
{
    $payment->update([
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $payment->subscription->update([
        'status' => 'active'
    ]);

    // Notification Save
    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'subscription',
        'notifiable_type' => 'App\\Models\\User',
        'notifiable_id' => $payment->user_id,
        'data' => json_encode([
            'title' => 'Subscription Activated',
            'message' => 'Your subscription has been activated successfully.',
            'subscription_id' => $payment->subscription_id,
        ]),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Payment verified'
    ]);
}

    public function cancelSubscription(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled'
        ]);
    }
}