<?php

declare(strict_types=1);

use App\Cart\CartTotals;
use App\Enums\DiscountType;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Builds cart lines in memory: the totals calculation must not need the database.
 *
 * @param  list<array{int, int}>  $lines  [unit price in cents, quantity]
 * @return Collection<int, CartItem>
 */
function cartLines(array $lines): Collection
{
    return collect($lines)->map(function (array $line): CartItem {
        [$price, $quantity] = $line;
        $variant = new ProductVariant(['price_override' => null])->setRelation('product', new Product(['base_price' => $price]));

        return new CartItem(['quantity' => $quantity])->setRelation('variant', $variant);
    });
}

function inMemoryCoupon(DiscountType $type, int $value, ?int $minSubtotal = null): Coupon
{
    return new Coupon(['type' => $type, 'value' => $value, 'min_subtotal' => $minSubtotal, 'is_active' => true, 'used_count' => 0]);
}

it('charges flat shipping below the free shipping threshold', function (): void {
    $totals = CartTotals::calculate(cartLines([[4000, 2]]), null);

    expect($totals)->toEqual(new CartTotals(itemCount: 2, subtotal: 8000, discount: 0, shipping: 490, total: 8490, freeThreshold: 10000));
});

it('ships for free from the threshold', function (): void {
    expect(CartTotals::calculate(cartLines([[5000, 2]]), null)->shipping)->toBe(0);
});

it('applies the threshold to the discounted subtotal', function (): void {
    $totals = CartTotals::calculate(cartLines([[10000, 1]]), inMemoryCoupon(DiscountType::Percent, 10));

    expect($totals->discount)->toBe(1000)
        ->and($totals->shipping)->toBe(490)
        ->and($totals->total)->toBe(9490);
});

it('caps a fixed discount at the subtotal', function (): void {
    $totals = CartTotals::calculate(cartLines([[1500, 1]]), inMemoryCoupon(DiscountType::Fixed, 2000));

    expect($totals->discount)->toBe(1500)
        ->and($totals->total)->toBe(490);
});

it('ignores a coupon whose minimum is not reached', function (): void {
    $totals = CartTotals::calculate(cartLines([[3000, 1]]), inMemoryCoupon(DiscountType::Percent, 20, minSubtotal: 5000));

    expect($totals->discount)->toBe(0);
});

it('returns zeros for an empty cart', function (): void {
    expect(CartTotals::calculate(new Collection, null))
        ->toEqual(new CartTotals(itemCount: 0, subtotal: 0, discount: 0, shipping: 0, total: 0, freeThreshold: 10000));
});
