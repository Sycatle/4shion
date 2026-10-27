<?php

declare(strict_types=1);

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

    /**
     * @param  array<mixed>  $object  the event's `data.object` (a Checkout Session or a Charge)
     */
    public function __construct(
        public string $eventId,
        public string $type,
        public array $object,
    ) {}

    public function handle(MarkOrderPaid $markOrderPaid, CancelOrder $cancelOrder, MarkOrderRefunded $markOrderRefunded): void
    {
        match ($this->type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->paid($markOrderPaid),
            'checkout.session.expired', 'checkout.session.async_payment_failed' => $this->expired($cancelOrder),
            'charge.refunded' => $this->refunded($markOrderRefunded),
            default => null,
        };

        StripeEvent::query()->whereKey($this->eventId)->update(['processed_at' => now()]);
    }

    private function paid(MarkOrderPaid $markOrderPaid): void
    {
        $order = $this->orderBy('stripe_session_id', 'id');

        if ($order === null || $this->string('payment_status') !== 'paid') {
            return;
        }

        $amount = $this->object['amount_total'] ?? null;

        if ($amount !== $order->total) {
            Log::error('Stripe amount does not match the order total.', [
                'order' => $order->number,
                'expected' => $order->total,
                'received' => $amount,
            ]);

            return;
        }

        $markOrderPaid($order, $this->string('payment_intent'));
    }

    private function expired(CancelOrder $cancelOrder): void
    {
        $order = $this->orderBy('stripe_session_id', 'id');

        if ($order !== null) {
            $cancelOrder($order);
        }
    }

    private function refunded(MarkOrderRefunded $markOrderRefunded): void
    {
        $order = $this->orderBy('stripe_payment_intent', 'payment_intent');

        if ($order !== null && ($this->object['refunded'] ?? false) === true) {
            $markOrderRefunded($order);
        }
    }

    private function orderBy(string $column, string $key): ?Order
    {
        $value = $this->string($key);

        return $value === null ? null : Order::query()->where($column, $value)->first();
    }

    private function string(string $key): ?string
    {
        $value = $this->object[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
