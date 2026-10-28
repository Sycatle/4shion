<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

final class MarkOrderPaid
{
    /** Gift cards bought in the shop stay valid for a year. */
    private const int GIFT_CARD_VALIDITY_MONTHS = 12;

    /**
     * Record a successful payment and issue the gift cards bought with it. Safe to call several
     * times for the same order (webhook retries, success page polling): only the first call has an effect.
     */
    public function __invoke(Order $order, ?string $paymentIntent = null): bool
    {
        $marked = DB::transaction(function () use ($order, $paymentIntent): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->status->canTransitionTo(OrderStatus::Paid)) {
                return false;
            }

            $order->transitionTo(OrderStatus::Paid, [
                'paid_at' => now(),
                'stripe_payment_intent' => $paymentIntent ?? $order->stripe_payment_intent,
            ]);

            if ($order->coupon_id !== null) {
                Coupon::query()->whereKey($order->coupon_id)->increment('used_count');
            }

            $order->items()->where('is_gift_card', true)->each(function (OrderItem $item) use ($order): void {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $item->giftCards()->create([
                        'code' => GiftCard::generateCode(),
                        'initial_value' => $item->unit_price,
                        'balance' => $item->unit_price,
                        'user_id' => $order->user_id,
                        'expires_at' => now()->addMonths(self::GIFT_CARD_VALIDITY_MONTHS),
                    ]);
                }
            });

            return true;
        });

        if ($marked) {
            event(new OrderPaid($order->refresh()));
        }

        return $marked;
    }
}
