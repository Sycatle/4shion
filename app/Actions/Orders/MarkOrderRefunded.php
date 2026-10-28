<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final readonly class MarkOrderRefunded
{
    public function __construct(private RestoreOrderResources $restoreResources) {}

    /**
     * Record a refund, put the items back in stock and credit the gift cards used. Shared by the
     * admin refund action and the `charge.refunded` webhook, so whichever arrives first wins and
     * the other is a no-op.
     */
    public function __invoke(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            // After a partial refund (return), the remaining stock and amounts are handled per return.
            if (! $order->status->canTransitionTo(OrderStatus::Refunded) || $order->refunded_total > 0) {
                return false;
            }

            ($this->restoreResources)($order, StockMovementReason::Refund);

            $order->transitionTo(OrderStatus::Refunded);

            return true;
        });
    }
}
