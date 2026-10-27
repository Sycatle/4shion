<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class MarkOrderRefunded
{
    /**
     * Record a refund and put the items back in stock. Shared by the admin refund action and the
     * `charge.refunded` webhook, so whichever arrives first wins and the other is a no-op.
     */
    public function __invoke(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->status->canTransitionTo(OrderStatus::Refunded)) {
                return false;
            }

            $order->load('items.variant');

            foreach ($order->items as $item) {
                $item->variant?->increment('stock', $item->quantity);
            }

            $order->transitionTo(OrderStatus::Refunded);

            return true;
        });
    }
}
