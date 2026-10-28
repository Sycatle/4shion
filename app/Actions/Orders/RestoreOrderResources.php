<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\StockMovementReason;
use App\Inventory\StockLedger;
use App\Models\GiftCardRedemption;
use App\Models\Order;
use App\Models\OrderItem;

/**
 * Undo what an order holds when it is cancelled or refunded: drop its pending pre-orders,
 * put its reserved units back in stock, credit the gift cards it was paid with and void
 * the gift cards it bought. Runs inside the caller's transaction, with the order locked.
 */
final readonly class RestoreOrderResources
{
    public function __construct(private StockLedger $ledger) {}

    public function __invoke(Order $order, StockMovementReason $reason): void
    {
        $order->load('items.variant', 'giftCardRedemptions');

        // Clear pre-orders first so the returned stock is not allocated back to this order.
        $reserved = $order->items->mapWithKeys(fn (OrderItem $item): array => [$item->id => $item->reservedQuantity()]);
        $order->items()->where('backordered_quantity', '>', 0)->update(['backordered_quantity' => 0]);

        foreach ($order->items as $item) {
            if ($item->is_gift_card) {
                $item->giftCards()->update(['is_active' => false]);

                continue;
            }

            $quantity = (int) $reserved->get($item->id);

            if ($item->variant !== null && $quantity > 0) {
                $this->ledger->add($item->variant, $quantity, $reason, $order);
            }
        }

        $order->giftCardRedemptions
            ->whereNull('reversed_at')
            ->each(function (GiftCardRedemption $redemption): void {
                $redemption->giftCard()->increment('balance', $redemption->amount);
                $redemption->update(['reversed_at' => now()]);
            });
    }
}
