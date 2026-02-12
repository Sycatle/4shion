<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class MarkOrderPaid
{
    /**
     * Record a successful payment. Safe to call several times for the same order
     * (webhook retries, success page polling): only the first call has an effect.
     */
    public function __invoke(Order $order, ?string $paymentIntent = null): bool
    {
        $marked = DB::transaction(function () use ($order, $paymentIntent): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status->isPaid()) {
                return false;
            }

            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'stripe_payment_intent' => $paymentIntent ?? $order->stripe_payment_intent,
            ]);

            if ($order->coupon_id !== null) {
                Coupon::query()->whereKey($order->coupon_id)->increment('used_count');
            }

            return true;
        });

        if ($marked) {
            OrderPaid::dispatch($order->refresh());
        }

        return $marked;
    }
}
