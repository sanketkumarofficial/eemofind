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
    /**
     * Plan List
     */
    public function plans()
    {
        return response()->json([
            'success' => true,
            'plans' => Plan::where('is_active', 1)->get()
        ]);
    }

    /**
     * User Subscription History
     */
    public function subscriptions(Request $request)
    {
        return response()->json([
            'success' => true,
            'subscriptions' => Subscription::with('plan')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get()
        ]);
    }

    /**
     * Payment History
     */
    public function payments(Request $request)
    {
        return response()->json([
            'success' => true,
            'payments' => Payment::where('user_id', $request->user()->id)
                ->latest()
                ->get()
        ]);
    }

    /**
     * Purchase Plan
     */
    public function purchase(Request $request)
    {
    $request->validate([
    'plan_id'             => 'required|exists:plans,id',
    'payment_method'      => 'required|in:razorpay',
    'payment_status'      => 'required|in:paid,pending,failed,cancelled',

        'razorpay_order_id'   => 'nullable|string',
        'razorpay_payment_id' => 'nullable|string',
        'razorpay_signature'  => 'nullable|string',
    ]);

    DB::beginTransaction();

    try {

        $plan = Plan::findOrFail($request->plan_id);

        $subscription = Subscription::where(
            'user_id',
            $request->user()->id
        )->first();

        if ($subscription) {

            $currentEndDate = $subscription->end_date &&
                $subscription->end_date > now()
                    ? \Carbon\Carbon::parse($subscription->end_date)
                    : now();

            $subscription->update([
                'plan_id'    => $plan->id,
                'amount'     => $plan->price,
                'start_date' => now(),
                'end_date'   => $currentEndDate->copy()->addMonth(),
                'status'     => $request->payment_status === 'paid'
                    ? 'active'
                    : 'pending',
            ]);

        } else {

            $subscription = Subscription::create([
                'user_id'    => $request->user()->id,
                'plan_id'    => $plan->id,
                'amount'     => $plan->price,
                'start_date' => now(),
                'end_date'   => now()->addMonth(),
                'status'     => $request->payment_status === 'paid'
                    ? 'active'
                    : 'pending',
            ]);
        }

        $payment = Payment::create([
            'user_id'         => $request->user()->id,
            'subscription_id' => $subscription->id,

            'gateway'         => 'razorpay',
            'order_id'        => $request->razorpay_order_id,
            'payment_id'      => $request->razorpay_payment_id,
            'signature'       => $request->razorpay_signature,

            'amount'          => $plan->price,
            'currency'        => 'INR',
            'status'          => $request->payment_status,
            'payment_method'  => $request->payment_method,

            'gateway_response' => $request->all(),

            'paid_at' => $request->payment_status === 'paid'
                ? now()
                : null,
        ]);

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => $request->payment_status === 'paid'
                ? 'subscription_success'
                : 'subscription_pending',

            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $request->user()->id,

            'data' => json_encode([
                'title' => $request->payment_status === 'paid'
                    ? 'Subscription Activated'
                    : 'Payment Pending',

                'message' => $request->payment_status === 'paid'
                    ? 'Your subscription has been activated.'
                    : 'Your payment is pending.',

                'plan_name' => $plan->name,
                'subscription_id' => $subscription->id,
            ]),

            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Plan processed successfully',
            'subscription' => $subscription,
            'payment' => $payment,
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }

    }




    /**
     * Verify Razorpay Payment
     */
    public function verifyPayment(Request $request, Payment $payment)
{
$request->validate([
'payment_status'      => 'required|in:paid,failed',
'razorpay_order_id'   => 'nullable|string',
'razorpay_payment_id' => 'nullable|string',
'razorpay_signature'  => 'nullable|string',
]);

DB::beginTransaction();

try {

    $payment->update([
        'status'     => $request->payment_status,
        'order_id'   => $request->razorpay_order_id,
        'payment_id' => $request->razorpay_payment_id,
        'signature'  => $request->razorpay_signature,
        'paid_at'    => $request->payment_status === 'paid'
            ? now()
            : null,
    ]);

    $payment->subscription->update([
        'status' => $request->payment_status === 'paid'
            ? 'active'
            : 'pending'
    ]);

    DB::commit();

    return response()->json([
        'success' => true,
        'message' => 'Payment updated successfully'
    ]);

} catch (\Exception $e) {

    DB::rollBack();

    return response()->json([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
}

}


    /**
     * Cancel Subscription
     */
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
