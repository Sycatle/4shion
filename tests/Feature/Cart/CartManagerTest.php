<?php

declare(strict_types=1);

use App\Cart\CartManager;
use App\Exceptions\Cart\CartException;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Auth\Events\Login;

function variant(int $price = 5000, int $stock = 10): ProductVariant
{
    $product = Product::factory()->create(['base_price' => $price]);

    return ProductVariant::factory()->for($product)->create(['stock' => $stock]);
}

it('adds items to a guest cart and sums quantities for the same variant', function (): void {
    $cart = app(CartManager::class);
    $variant = variant();

    $cart->add($variant, 1);
    $cart->add($variant, 2);

    expect($cart->count())->toBe(3)
        ->and($cart->items())->toHaveCount(1);
});

it('refuses quantities above the available stock', function (): void {
    $cart = app(CartManager::class);

    $cart->add(variant(stock: 2), 3);
})->throws(CartException::class, 'Stock insuffisant');

it('refuses out of stock variants', function (): void {
    app(CartManager::class)->add(variant(stock: 0), 1);
})->throws(CartException::class);

it('updates and removes lines', function (): void {
    $cart = app(CartManager::class);
    $item = $cart->add(variant(), 1);

    $cart->updateQuantity($item->id, 4);
    expect($cart->count())->toBe(4);

    $cart->updateQuantity($item->id, 0);
    expect($cart->count())->toBe(0);
});

it('charges flat shipping below the free shipping threshold', function (): void {
    $cart = app(CartManager::class);
    $cart->add(variant(price: 4000), 1);

    $totals = $cart->totals();

    expect($totals->subtotal)->toBe(4000)
        ->and($totals->shipping)->toBe(490)
        ->and($totals->total)->toBe(4490);
});

it('offers free shipping from the threshold', function (): void {
    $cart = app(CartManager::class);
    $cart->add(variant(price: 10000), 1);

    expect($cart->totals()->shipping)->toBe(0)
        ->and($cart->totals()->total)->toBe(10000);
});

it('applies a percentage coupon', function (): void {
    $cart = app(CartManager::class);
    $cart->add(variant(price: 5000), 2);
    Coupon::factory()->create(['code' => 'BIENVENUE10', 'value' => 10]);

    $cart->applyCoupon(' bienvenue10 ');

    expect($cart->totals()->discount)->toBe(1000)
        ->and($cart->totals()->total)->toBe(9490);
});

it('applies a fixed coupon without exceeding the subtotal', function (): void {
    $cart = app(CartManager::class);
    $cart->add(variant(price: 1500), 1);
    Coupon::factory()->fixed(5000)->create(['code' => 'BIG']);

    $cart->applyCoupon('BIG');

    expect($cart->totals()->discount)->toBe(1500);
});

it('rejects unknown, expired and below-minimum coupons', function (string $code): void {
    $cart = app(CartManager::class);
    $cart->add(variant(price: 3000), 1);
    Coupon::factory()->expired()->create(['code' => 'OLD']);
    Coupon::factory()->create(['code' => 'BIGSPENDER', 'min_subtotal' => 20000]);

    $cart->applyCoupon($code);
})->with(['UNKNOWN', 'OLD', 'BIGSPENDER'])->throws(CartException::class);

it('merges the guest cart into the user cart on login', function (): void {
    $variant = variant();
    $cart = app(CartManager::class);
    $cart->add($variant, 2);

    $user = User::factory()->create();
    $existing = $user->id;
    Cart::create(['user_id' => $existing])->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

    $this->actingAs($user);
    event(new Login('web', $user, false));

    expect(app(CartManager::class)->count())->toBe(3)
        ->and(Cart::whereNull('user_id')->count())->toBe(0);
});
