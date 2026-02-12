<?php

namespace App\Actions\Orders;

use App\Cart\CartManager;
use App\Enums\OrderStatus;
use App\Exceptions\Cart\CartException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Payments\CheckoutGateway;
use Illuminate\Support\Facades\DB;
use Throwable;

class CancelOrder
{
    public function __construct(private CartManager $cart, private CheckoutGateway $gateway) {}

    /**
     * Cancel a pending order, release its reserved stock and close the payment session.
     * Orders that are no longer pending are left untouched, so the action is idempotent.
     *
     * @param  bool  $restoreCart  put the items back in the current visitor's cart
     */
    public function __invoke(Order $order, bool $restoreCart = false): void
    {
        $cancelled = DB::transaction(function () use ($order): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::Pending) {
                return false;
            }

            $order->load('items.variant');

            foreach ($order->items as $item) {
                $item->variant?->increment('stock', $item->quantity);
            }

            $order->update(['status' => OrderStatus::Cancelled]);

            return true;
        });

        if (! $cancelled) {
            return;
        }

        $order->refresh();

        if ($order->stripe_session_id !== null) {
            try {
                $this->gateway->expireSession($order->stripe_session_id);
            } catch (Throwable $e) {
                // The session may already be expired or completed on the provider side.
                report($e);
            }
        }

        if ($restoreCart) {
            $order->items->each(function (OrderItem $item): void {
                if ($item->variant === null) {
                    return;
                }

                try {
                    $this->cart->add($item->variant, $item->quantity);
                } catch (CartException) {
                    // Item no longer available in that quantity: skip it.
                }
            });
        }
    }
}
