<?php

declare(strict_types=1);

namespace App\Payments;

use App\Models\Order;
use App\Models\OrderItem;
use Laravel\Cashier\Cashier;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

final class StripeCheckoutGateway implements CheckoutGateway
{
    /** Stripe requires a session lifetime between 30 minutes and 24 hours. */
    private const int SESSION_LIFETIME_MINUTES = 30;

    public function createSession(Order $order): CheckoutSession
    {
        $order->loadMissing('items', 'user');
        $stripe = $order->user->stripe();
        $currency = strtolower($order->currency);
        $metadata = ['order_id' => (string) $order->id, 'order_number' => $order->number];

        $data = [
            'mode' => Session::MODE_PAYMENT,
            'customer' => $order->user->createOrGetStripeCustomer()->id,
            'client_reference_id' => $order->number,
            'locale' => 'fr',
            'expires_at' => now()->addMinutes(self::SESSION_LIFETIME_MINUTES)->getTimestamp(),
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel', $order),
            'metadata' => $metadata,
            'payment_intent_data' => ['metadata' => $metadata],
            'line_items' => $order->items->map(fn (OrderItem $item): array => [
                'quantity' => $item->quantity,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $item->unit_price,
                    'product_data' => ['name' => "{$item->product_name} — {$item->variant_label}"],
                ],
            ])->all(),
        ];

        if ($order->discount_total > 0) {
            $coupon = $stripe->coupons->create([
                'amount_off' => $order->discount_total,
                'currency' => $currency,
                'duration' => 'once',
                'name' => $order->coupon_code ?? 'Réduction',
            ], ['idempotency_key' => "order-{$order->id}-coupon"]);

            $data['discounts'] = [['coupon' => $coupon->id]];
        }

        if ($order->shipping_total > 0) {
            $data['shipping_options'] = [[
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'display_name' => 'Livraison standard',
                    'fixed_amount' => ['amount' => $order->shipping_total, 'currency' => $currency],
                ],
            ]];
        }

        $session = $stripe->checkout->sessions->create($data, ['idempotency_key' => "order-{$order->id}-session"]);

        return $this->toSession($session);
    }

    public function retrieveSession(string $sessionId): CheckoutSession
    {
        return $this->toSession($this->client()->checkout->sessions->retrieve($sessionId));
    }

    public function expireSession(string $sessionId): void
    {
        $this->client()->checkout->sessions->expire($sessionId);
    }

    public function refund(string $paymentIntent): void
    {
        $this->client()->refunds->create(
            ['payment_intent' => $paymentIntent],
            ['idempotency_key' => "refund-{$paymentIntent}"],
        );
    }

    private function client(): StripeClient
    {
        return Cashier::stripe();
    }

    private function toSession(Session $session): CheckoutSession
    {
        return new CheckoutSession(
            id: $session->id,
            url: $session->url,
            paid: $session->payment_status === Session::PAYMENT_STATUS_PAID,
            paymentIntent: is_string($session->payment_intent) ? $session->payment_intent : null,
            amountTotal: $session->amount_total,
        );
    }
}
