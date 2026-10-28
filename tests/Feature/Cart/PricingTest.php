<?php

declare(strict_types=1);

use App\Actions\Checkout\CreateOrder;
use App\Cart\CartManager;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Exceptions\Cart\CartException;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\User;

it('prices variants with the running sale and keeps the regular price for strike-through', function (): void {
    $product = Product::factory()->onSale(20)->create(['base_price' => 10000]);
    $variant = ProductVariant::factory()->for($product)->create(['price_override' => 12500]);

    expect($variant->price())->toBe(10000)
        ->and($variant->regularPrice())->toBe(12500)
        ->and($variant->isOnSale())->toBeTrue();
});

it('ignores a sale that has not started or has ended', function (): void {
    $future = Product::factory()->onSale()->create(['base_price' => 10000, 'sale_starts_at' => now()->addDay()]);
    $past = Product::factory()->onSale()->create(['base_price' => 10000, 'sale_ends_at' => now()->subDay()]);

    expect($future->price())->toBe(10000)->and($past->price())->toBe(10000);
});

it('charges the sale price on the order', function (): void {
    $variant = ProductVariant::factory()->for(Product::factory()->onSale(20)->create(['base_price' => 10000]))->create(['stock' => 5]);
    app(CartManager::class)->add($variant);

    $order = app(CreateOrder::class)(User::factory()->create(), address());

    expect($order->items->sole()->unit_price)->toBe(8000)->and($order->subtotal)->toBe(8000);
});

it('applies a running automatic promotion and records it on the order', function (): void {
    Promotion::factory()->create();
    Promotion::factory()->create(['is_active' => false, 'percent' => 50]);
    cartWith(price: 20000);

    $order = app(CreateOrder::class)(User::factory()->create(), address());

    expect($order->discount_total)->toBe(2000)
        ->and($order->promotion_name)->toBe('-10 % dès 150 €')
        ->and($order->coupon_code)->toBeNull()
        ->and($order->discountLabel())->toBe('-10 % dès 150 €');
});

it('does not apply an ended promotion', function (): void {
    Promotion::factory()->create(['ends_at' => now()->subHour()]);
    cartWith(price: 20000);

    expect(app(CartManager::class)->totals()->discount)->toBe(0);
});

it('reserves a first-order coupon for customers without a paid order', function (): void {
    $coupon = Coupon::factory()->create(['code' => 'BIENVENUE', 'first_order_only' => true]);
    $returning = User::factory()->create();
    Order::factory()->for($returning)->create(['status' => OrderStatus::Paid]);

    expect($coupon->isUsableBy(User::factory()->create()))->toBeTrue()
        ->and($coupon->isUsableBy($returning))->toBeFalse();

    $this->actingAs($returning);
    cartWith();
    app(CartManager::class)->applyCoupon('BIENVENUE');
})->throws(CartException::class, 'première commande');

it('limits the number of uses per customer', function (): void {
    $coupon = Coupon::factory()->create(['max_uses_per_user' => 1]);
    $user = User::factory()->create();

    expect($coupon->isUsableBy($user))->toBeTrue();

    Order::factory()->for($user)->create(['status' => OrderStatus::Paid, 'coupon_id' => $coupon->id]);

    expect($coupon->isUsableBy($user))->toBeFalse()
        ->and($coupon->isUsableBy(User::factory()->create()))->toBeTrue();
});

it('restricts a coupon to its categories', function (): void {
    $shirts = Category::factory()->create();
    $coupon = Coupon::factory()->create(['value' => 50]);
    $coupon->categories()->attach($shirts);

    $shirt = ProductVariant::factory()->for(Product::factory()->for($shirts)->create(['base_price' => 4000]))->create(['stock' => 5]);
    $other = ProductVariant::factory()->for(Product::factory()->create(['base_price' => 6000]))->create(['stock' => 5]);
    app(CartManager::class)->add($shirt);
    app(CartManager::class)->add($other);
    app(CartManager::class)->applyCoupon($coupon->code);

    expect(app(CartManager::class)->totals()->discount)->toBe(2000);
});

it('can exclude sale items from a coupon', function (): void {
    $coupon = Coupon::factory()->create(['value' => 50, 'excludes_sale_items' => true]);
    $sale = ProductVariant::factory()->for(Product::factory()->onSale(50)->create(['base_price' => 10000]))->create(['stock' => 5]);
    $regular = ProductVariant::factory()->for(Product::factory()->create(['base_price' => 4000]))->create(['stock' => 5]);
    app(CartManager::class)->add($sale);
    app(CartManager::class)->add($regular);
    app(CartManager::class)->applyCoupon($coupon->code);

    expect(app(CartManager::class)->totals()->discount)->toBe(2000);
});

it('offers shipping with a free shipping coupon', function (): void {
    Coupon::factory()->create(['code' => 'PORTOFFERT', 'type' => DiscountType::FreeShipping, 'value' => 0]);
    cartWith(price: 3000);
    app(CartManager::class)->applyCoupon('PORTOFFERT');

    $order = app(CreateOrder::class)(User::factory()->create(), address());

    expect($order->shipping_total)->toBe(0)
        ->and($order->total)->toBe(3000)
        ->and($order->coupon_code)->toBe('PORTOFFERT');
});
