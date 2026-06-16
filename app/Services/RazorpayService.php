<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use RuntimeException;

class RazorpayService
{
    public function __construct(private readonly SettingService $settings, private readonly SubscriptionService $subscriptions) {}

    public function createOrder(User $user, Subscription $subscription): Payment
    {
        $api = $this->api();
        $order = $api->order->create(['receipt' => 'sub_'.$subscription->id.'_'.time(), 'amount' => (int) round($subscription->amount * 100), 'currency' => $this->currency()]);

        return Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'order_id' => $order['id'], 'amount' => $subscription->amount, 'currency' => $this->currency(), 'status' => 'pending', 'gateway_response' => $order->toArray()]);
    }

    public function verify(Payment $payment, array $payload): Payment
    {
        return DB::transaction(function () use ($payment, $payload) {
            $this->api()->utility->verifyPaymentSignature(['razorpay_order_id' => $payment->order_id, 'razorpay_payment_id' => $payload['razorpay_payment_id'], 'razorpay_signature' => $payload['razorpay_signature']]);
            $gatewayPayment = $this->api()->payment->fetch($payload['razorpay_payment_id']);
            $payment->update(['payment_id' => $payload['razorpay_payment_id'], 'signature' => $payload['razorpay_signature'], 'status' => 'success', 'payment_method' => $gatewayPayment['method'] ?? null, 'gateway_response' => $gatewayPayment->toArray(), 'paid_at' => now()]);
            if ($payment->subscription) {
                $this->subscriptions->activate($payment->subscription);
            }

            return $payment->fresh();
        });
    }

    public function markFailed(Payment $payment, array $response = []): Payment
    {
        $payment->update(['status' => 'failed', 'gateway_response' => $response]);

        return $payment->fresh();
    }

    private function api(): Api
    {
        $key = $this->settings->get('razorpay_key', config('eemo.razorpay.key'));
        $secret = $this->settings->get('razorpay_secret', config('eemo.razorpay.secret'));
        if (! $key || ! $secret) {
            throw new RuntimeException('Razorpay credentials are not configured.');
        }

        return new Api($key, $secret);
    }

    private function currency(): string
    {
        return (string) config('eemo.razorpay.currency', 'INR');
    }
}
