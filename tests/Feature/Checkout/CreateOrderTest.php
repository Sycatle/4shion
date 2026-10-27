<?php

use App\Actions\Checkout\CreateOrder;
use App\Cart\CartManager;
use App\Enums\OrderStatus;
use App\Exceptions\Cart\CartException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;

it('creates a pending order, snapshots the items and reserves the stock', function () {
    $user = User::factory()->create();
    $variant = cartWith(price: 4000, stock: 5, quantity: 2);

    $order = app(CreateOrder::class)($user, address());

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->number)->toBe(Order::numberFor($order->id))
        ->and($order->subtotal)->toBe(8000)
        ->and($order->shipping_total)->toBe(490)
        ->and($order->total)->toBe(8490)
        ->and($order->vat_rate)->toBe('20.00')
        ->and($order->vat_total)->toBe(1415)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->product_name)->toBe($variant->product->name)
        ->and($order->items->first()->unit_price)->toBe(4000)
        ->and($order->shipping_address->city)->toBe('Paris')
        ->and($variant->fresh()->stock)->toBe(3)
        ->and(app(CartManager::class)->count())->toBe(0);
});

it('stores the coupon applied to the cart', function () {
    $user = User::factory()->create();
    cartWith(price: 10000);
    Coupon::factory()->create(['code' => 'BIENVENUE10']);
    app(CartManager::class)->applyCoupon('BIENVENUE10');

    $order = app(CreateOrder::class)($user, address());

    expect($order->discount_total)->toBe(1000)
        ->and($order->coupon_code)->toBe('BIENVENUE10')
        ->and($order->total)->toBe(9490);
});

it('rejects an empty cart', function () {
    app(CreateOrder::class)(User::factory()->create(), address());
})->throws(CartException::class, 'Votre panier est vide.');

it('does not create anything when an item ran out of stock', function () {
    $user = User::factory()->create();
    $variant = cartWith(stock: 3, quantity: 3);
    $variant->update(['stock' => 1]); // someone else bought it meanwhile

    expect(fn () => app(CreateOrder::class)($user, address()))->toThrow(CartException::class);

    expect(Order::count())->toBe(0)
        ->and($variant->fresh()->stock)->toBe(1)
        ->and(app(CartManager::class)->count())->toBe(3);
});

it('rejects products that were deactivated', function () {
    $user = User::factory()->create();
    $variant = cartWith();
    $variant->product->update(['is_active' => false]);

    app(CreateOrder::class)($user, address());
})->throws(CartException::class);

it('sells the last unit only once', function () {
    $variant = cartWith(stock: 1);
    $first = User::factory()->create();
    $second = User::factory()->create();

    app(CreateOrder::class)($first, address());

    // The second customer still holds the item in a cart when the stock is gone.
    app(CartManager::class)->add($variant->fresh()->forceFill(['stock' => 1]));

    expect(fn () => app(CreateOrder::class)($second, address()))->toThrow(CartException::class)
        ->and($variant->fresh()->stock)->toBe(0)
        ->and(Order::count())->toBe(1);
});
