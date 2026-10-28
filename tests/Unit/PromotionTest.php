<?php

declare(strict_types=1);

use App\Cart\CartTotals;
use App\Cart\PricedLine;
use App\Enums\DiscountType;
use App\Enums\PromotionType;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Support\Collection;

function line(int $price, int $quantity = 1, ?int $categoryId = 1, bool $giftCard = false): PricedLine
{
    return new PricedLine($price, $price, $quantity, $categoryId, $giftCard);
}

/** @param  list<array{int, int}>  $lines  [unit price in cents, quantity] */
function itemsOf(array $lines): Collection
{
    return collect($lines)->map(function (array $line): CartItem {
        [$price, $quantity] = $line;
        $variant = new ProductVariant(['price_override' => null])->setRelation('product', new Product(['base_price' => $price, 'is_gift_card' => false]));

        return new CartItem(['quantity' => $quantity])->setRelation('variant', $variant);
    });
}

function spendPromotion(int $minSubtotal = 15000, int $percent = 10): Promotion
{
    return new Promotion(['name' => "-{$percent} %", 'type' => PromotionType::SpendThreshold, 'min_subtotal' => $minSubtotal, 'percent' => $percent]);
}

it('discounts a spend threshold promotion only once the threshold is reached', function (): void {
    expect(spendPromotion()->discountFor([line(10000), line(5000)]))->toBe(1500)
        ->and(spendPromotion()->discountFor([line(14999)]))->toBe(0);
});

it('gives the cheapest unit of every group free', function (): void {
    $promotion = new Promotion(['type' => PromotionType::BuyXGetCheapestFree, 'buy_quantity' => 3]);

    expect($promotion->discountFor([line(5000), line(3000, 2)]))->toBe(3000)
        ->and($promotion->discountFor([line(5000, 6)]))->toBe(10000)
        ->and($promotion->discountFor([line(5000, 2)]))->toBe(0);
});

it('ignores gift cards and other categories', function (): void {
    $promotion = new Promotion(['type' => PromotionType::SpendThreshold, 'min_subtotal' => 1000, 'percent' => 10, 'category_id' => 1]);

    expect($promotion->discountFor([line(10000, categoryId: 2), line(10000, giftCard: true), line(2000, categoryId: 1)]))->toBe(200);
});

it('applies the most favourable of the coupon and the automatic promotion, never both', function (): void {
    $promotions = collect([spendPromotion()]);
    $weakCoupon = new Coupon(['type' => DiscountType::Percent, 'value' => 5, 'is_active' => true, 'used_count' => 0]);
    $strongCoupon = new Coupon(['code' => 'BIG', 'type' => DiscountType::Percent, 'value' => 20, 'is_active' => true, 'used_count' => 0]);

    $withWeak = CartTotals::calculate(itemsOf([[20000, 1]]), $weakCoupon, $promotions);
    $withStrong = CartTotals::calculate(itemsOf([[20000, 1]]), $strongCoupon, $promotions);

    expect($withWeak->discount)->toBe(2000)
        ->and($withWeak->appliedPromotion?->name)->toBe('-10 %')
        ->and($withWeak->appliedCoupon)->toBeNull()
        ->and($withWeak->couponNotice)->toContain('plus avantageuse')
        ->and($withStrong->discount)->toBe(4000)
        ->and($withStrong->appliedCoupon?->code)->toBe('BIG')
        ->and($withStrong->appliedPromotion)->toBeNull();
});

it('waives shipping with a free shipping coupon', function (): void {
    $coupon = new Coupon(['code' => 'LIVRAISON', 'type' => DiscountType::FreeShipping, 'value' => 0, 'is_active' => true, 'used_count' => 0]);

    $totals = CartTotals::calculate(itemsOf([[3000, 1]]), $coupon);

    expect($totals->shipping)->toBe(0)
        ->and($totals->discount)->toBe(0)
        ->and($totals->total)->toBe(3000)
        ->and($totals->appliedCoupon?->code)->toBe('LIVRAISON');
});

it('settles part of the total with a gift card', function (): void {
    $card = new GiftCard(['balance' => 2000, 'initial_value' => 2000, 'is_active' => true]);

    $totals = CartTotals::calculate(itemsOf([[5000, 1]]), null, giftCard: $card);

    expect($totals->total)->toBe(5490)
        ->and($totals->giftCard)->toBe(2000)
        ->and($totals->amountDue())->toBe(3490);
});

it('caps the gift card at the order total', function (): void {
    $card = new GiftCard(['balance' => 20000, 'initial_value' => 20000, 'is_active' => true]);

    $totals = CartTotals::calculate(itemsOf([[5000, 1]]), null, giftCard: $card);

    expect($totals->giftCard)->toBe(5490)
        ->and($totals->amountDue())->toBe(0);
});
