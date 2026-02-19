<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

it('lets a customer view only their own orders', function () {
    $owner = User::factory()->create();
    $order = Order::factory()->for($owner)->create();

    expect($owner->can('view', $order))->toBeTrue()
        ->and(User::factory()->create()->can('view', $order))->toBeFalse();
});

it('lets an admin view any order', function () {
    $order = Order::factory()->create();

    expect(User::factory()->create(['is_admin' => true])->can('view', $order))->toBeTrue();
});

it('forbids opening the order of another customer', function () {
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('account.orders.show', $order))
        ->assertForbidden();
});

it('only lets customers who bought a product review it', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create();

    expect($user->can('review', $product))->toBeFalse();

    $order = Order::factory()->for($user)->paid()->create();
    OrderItem::create([
        'order_id' => $order->id, 'product_variant_id' => $variant->id, 'product_name' => $product->name,
        'variant_label' => 'Noir / M', 'sku' => $variant->sku, 'unit_price' => 5000, 'quantity' => 1, 'total' => 5000,
    ]);

    expect($user->can('review', $product))->toBeTrue();
});
