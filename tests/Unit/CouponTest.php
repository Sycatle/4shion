<?php

declare(strict_types=1);

use App\Enums\DiscountType;
use App\Models\Coupon;

function couponWith(array $attributes = []): Coupon
{
    return new Coupon([
        'type' => DiscountType::Percent,
        'value' => 10,
        'is_active' => true,
        'used_count' => 0,
        ...$attributes,
    ]);
}

it('is usable when active, started, not expired and not exhausted', function (): void {
    expect(couponWith(['starts_at' => now()->subDay(), 'expires_at' => now()->addDay(), 'max_uses' => 5, 'used_count' => 4])->isUsable())->toBeTrue();
});

it('is not usable outside its window, when disabled or exhausted', function (array $attributes): void {
    expect(couponWith($attributes)->isUsable())->toBeFalse();
})->with([
    'disabled' => [['is_active' => false]],
    'not started' => [['starts_at' => now()->addDay()]],
    'expired' => [['expires_at' => now()->subMinute()]],
    'exhausted' => [['max_uses' => 3, 'used_count' => 3]],
]);

it('rounds percentage discounts down to the cent', function (): void {
    expect(couponWith(['value' => 15])->discountFor(999))->toBe(149);
});
