<?php

namespace App\Cart;

use App\Models\CartItem;
use App\Models\Coupon;
use Illuminate\Support\Collection;

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
        $subtotal = $items->sum(fn (CartItem $item) => $item->variant->price() * $item->quantity);
        $itemCount = $items->sum('quantity');

        $discount = $coupon !== null && $coupon->isUsable() && $coupon->meetsMinimum($subtotal)
            ? $coupon->discountFor($subtotal)
            : 0;

        $discounted = $subtotal - $discount;
        $shipping = $itemCount === 0 || $discounted >= config('shop.shipping.free_threshold')
            ? 0
            : config('shop.shipping.flat_rate');

        return new self($itemCount, $subtotal, $discount, $shipping, $discounted + $shipping);
    }
}
