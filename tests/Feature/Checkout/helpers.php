<?php

declare(strict_types=1);

use App\Actions\Checkout\CreateOrder;
use App\Cart\CartManager;
use App\Data\ShippingAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Payments\CheckoutGateway;
use Tests\Support\FakeCheckoutGateway;

function fakeGateway(): FakeCheckoutGateway
{
    $gateway = new FakeCheckoutGateway;
    app()->instance(CheckoutGateway::class, $gateway);

    return $gateway;
}

function address(): ShippingAddress
{
    return new ShippingAddress('Camille', 'Martin', '12 rue des Lilas', null, '75011', 'Paris', 'FR', '0600000000');
}

function cartWith(int $price = 5000, int $stock = 10, int $quantity = 1): ProductVariant
{
    $variant = ProductVariant::factory()->for(Product::factory()->create(['base_price' => $price]))->create(['stock' => $stock]);
    app(CartManager::class)->add($variant, $quantity);

    return $variant;
}

function pendingOrder(User $user, int $quantity = 1): Order
{
    cartWith(quantity: $quantity);

    return (new CreateOrder(app(CartManager::class)))($user, address());
}
