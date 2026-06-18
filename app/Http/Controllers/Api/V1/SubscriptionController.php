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

                $baseDate = $subscription->end_date &&
                    $subscription->end_date > now()
                        ? \Carbon\Carbon::parse($subscription->end_date)
                        : now();

                $subscription->update([
                    'plan_id'    => $plan->id,
                    'amount'     => $plan->price,
                    'start_date' => now()->toDateString(),
                    'end_date'   => $baseDate->copy()->addMonth()->toDateString(),
                    'status'     => $request->payment_status == 'paid'
                        ? 'active'
                        : 'pending',
                ]);

            } else {

                $subscription = Subscription::create([
                    'user_id'    => $request->user()->id,
                    'plan_id'    => $plan->id,
                    'amount'     => $plan->price,
                    'start_date' => now()->toDateString(),
                    'end_date'   => now()->addMonth()->toDateString(),
                    'status'     => $request->payment_status == 'paid'
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

                'paid_at' => $request->payment_status == 'paid'
                    ? now()
                    : null,
            ]);

            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'subscription',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $request->user()->id,
                'data' => json_encode([
                    'title' => $request->payment_status == 'paid'
                        ? 'Subscription Activated'
                        : 'Payment Pending',
                    'message' => $request->payment_status == 'paid'
                        ? 'Your subscription activated successfully.'
                        : 'Your payment is pending.',
                    'plan_name' => $plan->name
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Plan processed successfully',
                'subscription' => $subscription,
                'payment' => $payment
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
     * Current Active Subscription
     */
            public function currentPlan(Request $request)
            {
                $subscription = Subscription::with('plan')
                    ->where('user_id', $request->user()->id)
                    ->first();

                return response()->json([
                    'success' => true,
                    'subscription' => $subscription
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

}
