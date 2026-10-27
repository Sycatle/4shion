<?php

namespace App\Jobs;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\MarkOrderRefunded;
use App\Models\Order;
use App\Models\StripeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Applies a Stripe event to the matching order. Every branch relies on idempotent actions,
 * so a retry after a partial failure is safe.
 */
final class ProcessStripeEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    /** @param  array<string, mixed>  $payload */
    public function __construct(public array $payload) {}

    public function handle(MarkOrderPaid $markOrderPaid, CancelOrder $cancelOrder, MarkOrderRefunded $markOrderRefunded): void
    {
        /** @var array<string, mixed> $object */
        $object = $this->payload['data']['object'] ?? [];

        match ($this->payload['type'] ?? null) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->paid($object, $markOrderPaid),
            'checkout.session.expired', 'checkout.session.async_payment_failed' => $this->expired($object, $cancelOrder),
            'charge.refunded' => $this->refunded($object, $markOrderRefunded),
            default => null,
        };

        StripeEvent::query()->whereKey($this->payload['id'] ?? null)->update(['processed_at' => now()]);
    }

    /** @param  array<string, mixed>  $session */
    private function paid(array $session, MarkOrderPaid $markOrderPaid): void
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

        $paymentIntent = $session['payment_intent'] ?? null;

        $markOrderPaid($order, is_string($paymentIntent) ? $paymentIntent : null);
    }

    /** @param  array<string, mixed>  $session */
    private function expired(array $session, CancelOrder $cancelOrder): void
    {
        $order = Order::query()->where('stripe_session_id', $session['id'] ?? null)->first();

        if ($order !== null) {
            $cancelOrder($order);
        }
    }

    /** @param  array<string, mixed>  $charge */
    private function refunded(array $charge, MarkOrderRefunded $markOrderRefunded): void
    {
        $order = Order::query()->where('stripe_payment_intent', $charge['payment_intent'] ?? null)->first();

        if ($order !== null && ($charge['refunded'] ?? false) === true) {
            $markOrderRefunded($order);
        }
    }
}
