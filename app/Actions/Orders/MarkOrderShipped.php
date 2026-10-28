<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\Carrier;
use App\Enums\OrderStatus;
use App\Events\OrderShipped;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class MarkOrderShipped
{
    public function __construct(private CreateShipment $createShipment) {}

    /**
     * Ship everything that is left on a paid order, in one parcel. Returns false when the order is
     * not in a shippable state, including when pre-ordered units are still waiting for stock.
     */
    public function __invoke(Order $order, ?Carrier $carrier = null, ?string $trackingNumber = null): bool
    {
        $order = Order::query()->with('items')->findOrFail($order->id);

        if (! $order->status->canTransitionTo(OrderStatus::Shipped) || $order->hasBackorders()) {
            return false;
        }

        $remaining = $order->items
            ->mapWithKeys(fn (OrderItem $item): array => [$item->id => $item->shippableQuantity()])
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->all();

        if ($remaining !== []) {
            try {
                ($this->createShipment)($order, $remaining, $carrier, $trackingNumber);
            } catch (InvalidArgumentException) {
                return false;
            }

            return true;
        }

        // Nothing physical left (digital-only order): just close it.
        $shipped = DB::transaction(function () use ($order): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->status->canTransitionTo(OrderStatus::Shipped)) {
                return false;
            }

            $order->transitionTo(OrderStatus::Shipped, ['shipped_at' => now()]);

            return true;
        });

        if ($shipped) {
            event(new OrderShipped($order->refresh()));
        }

        return $shipped;
    }
}
