<?php

namespace App\Services;

use App\Events\PaymentFailed;
use App\Events\PaymentSuccess;
use App\Models\Payment;
use App\Models\User;

class PaymentService
{
    public function __construct(private RazorpayService $razorpayService)
    {
    }

    public function createOrder(User $user, float $amount, string $currency = 'INR'): Payment
    {
        $order = $this->razorpayService->createOrder($amount, $currency, ['user_id' => $user->id]);

        return Payment::create([
            'user_id' => $user->id,
            'gateway' => 'razorpay',
            'gateway_order_id' => $order['id'] ?? null,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'created',
            'gateway_response' => $order,
        ]);
    }

    public function capture(Payment $payment, array $payload): Payment
    {
        try {
            $this->razorpayService->verifyPayment($payload);
            $payment->forceFill([
                'gateway_payment_id' => $payload['razorpay_payment_id'],
                'gateway_signature' => $payload['razorpay_signature'],
                'status' => 'paid',
                'paid_at' => now(),
                'gateway_response' => $payload,
            ])->save();
            event(new PaymentSuccess(['payment_id' => $payment->id]));
        } catch (\Throwable $exception) {
            $payment->forceFill(['status' => 'failed', 'gateway_response' => $payload + ['error' => $exception->getMessage()]])->save();
            event(new PaymentFailed(['payment_id' => $payment->id]));
            throw $exception;
        }

        return $payment;
    }

    public function handle(array $payload = []): array
    {
        $payment = $this->createOrder(User::findOrFail($payload['user_id']), (float) $payload['amount'], $payload['currency'] ?? 'INR');

        return ['ok' => true, 'payment_id' => $payment->id];
    }
}
