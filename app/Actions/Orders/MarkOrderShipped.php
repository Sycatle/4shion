<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderShipped;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class MarkOrderShipped
{
    /** Move a paid order to shipped. Returns false when the order is not in a shippable state. */
    public function __invoke(Order $order): bool
    {
        $shipped = DB::transaction(function () use ($order): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::Paid) {
                return false;
            }

            $order->update(['status' => OrderStatus::Shipped, 'shipped_at' => now()]);

            return true;
        });

        if ($shipped) {
            OrderShipped::dispatch($order->refresh());
        }

        return $shipped;
    }
}
