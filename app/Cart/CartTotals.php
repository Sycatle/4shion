<?php

declare(strict_types=1);

namespace App\Cart;

use App\Models\CartItem;
use App\Models\Coupon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

final readonly class CartTotals
{
    public function __construct(
        public int $itemCount,
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public int $total,
    ) {}

    /**
     * All amounts are in cents. A coupon that no longer applies is silently ignored.
     *
     * @param  Collection<int, CartItem>  $items
     */
    public static function calculate(Collection $items, ?Coupon $coupon): self
    {
        $subtotal = $items->sum(fn (CartItem $item): int => $item->variant->price() * $item->quantity);
        $itemCount = $items->sum(fn (CartItem $item): int => $item->quantity);

        $discount = $coupon instanceof Coupon && $coupon->isUsable() && $coupon->meetsMinimum($subtotal)
            ? $coupon->discountFor($subtotal)
            : 0;

        $discounted = $subtotal - $discount;
        $shipping = $itemCount === 0 || $discounted >= Config::integer('shop.shipping.free_threshold')
            ? 0
            : Config::integer('shop.shipping.flat_rate');

        return new self($itemCount, $subtotal, $discount, $shipping, $discounted + $shipping);
    }
}
