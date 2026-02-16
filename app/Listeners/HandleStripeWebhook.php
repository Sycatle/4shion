<?php

namespace App\Listeners;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\MarkOrderRefunded;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Reacts to Stripe events. Signature verification is done by Cashier's webhook middleware
 * before this listener runs; every handler is idempotent because Stripe retries deliveries.
 */
class HandleStripeWebhook
{
    public function __construct(private MarkOrderPaid $markOrderPaid,
        private CancelOrder $cancelOrder,
        private MarkOrderRefunded $markOrderRefunded,
    ) {}

    public function handle(WebhookReceived $event): void
    {
        /** @var array<string, mixed> $object */
        $object = $event->payload['data']['object'] ?? [];

        match ($event->payload['type'] ?? null) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->paid($object),
            'checkout.session.expired', 'checkout.session.async_payment_failed' => $this->expired($object),
            'charge.refunded' => $this->refunded($object),
            default => null,
        };
    }

    /** @param  array<string, mixed>  $session */
    private function paid(array $session): void
    {
        $order = Order::query()->where('stripe_session_id', $session['id'] ?? null)->first();

        if ($order === null || ($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        if (($session['amount_total'] ?? null) !== $order->total) {
            Log::error('Stripe amount does not match the order total.', [
                'order' => $order->number,
                'expected' => $order->total,
                'received' => $session['amount_total'] ?? null,
            ]);

            return;
        }

        ($this->markOrderPaid)($order, $session['payment_intent'] ?? null);
    }

    /** @param  array<string, mixed>  $session */
    private function expired(array $session): void
    {
        $order = Order::query()->where('stripe_session_id', $session['id'] ?? null)->first();

        if ($order !== null) {
            ($this->cancelOrder)($order);
        }
    }

    /** @param  array<string, mixed>  $charge */
    private function refunded(array $charge): void
    {
        $order = Order::query()->where('stripe_payment_intent', $charge['payment_intent'] ?? null)->first();

        if ($order !== null && ($charge['refunded'] ?? false) === true) {
            ($this->markOrderRefunded)($order);
        }
    }
}
