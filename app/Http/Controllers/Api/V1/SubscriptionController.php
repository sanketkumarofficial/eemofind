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
    public function purchase(Request $request, Plan $plan)
    {
        DB::beginTransaction();

        try {

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

            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'subscription_pending',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $request->user()->id,
                'data' => json_encode([
                    'title' => 'Plan Purchase Started',
                    'message' => 'Payment process initiated.',
                    'plan' => $plan->name
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'amount' => $plan->price,
                'message' => 'Proceed with Razorpay payment'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Verify Razorpay Payment
     */
    public function verifyPayment(Request $request, Payment $payment)
    {
        $request->validate([
            'razorpay_payment_id' => 'required',
            'razorpay_order_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        DB::beginTransaction();

        try {

            $payment->update([
                'status' => 'paid',
                'gateway_payment_id' => $request->razorpay_payment_id,
                'gateway_order_id' => $request->razorpay_order_id,
                'gateway_response' => $request->all(),
                'paid_at' => now(),
            ]);

            $payment->subscription->update([
                'status' => 'active'
            ]);

            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'subscription_success',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $payment->user_id,
                'data' => json_encode([
                    'title' => 'Subscription Activated',
                    'message' => 'Your plan activated successfully.'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'subscription_failed',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $payment->user_id,
                'data' => json_encode([
                    'title' => 'Payment Failed',
                    'message' => $e->getMessage()
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ],500);
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
