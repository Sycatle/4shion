<?php

use App\Actions\Orders\GenerateInvoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

function paidOrderWithItem(?User $user = null): Order
{
    $order = Order::factory()->for($user ?? User::factory()->create())->paid()->create([
        'subtotal' => 8900, 'discount_total' => 0, 'shipping_total' => 0, 'total' => 8900,
    ]);
    OrderItem::create([
        'order_id' => $order->id, 'product_name' => 'Hoodie Atlas', 'variant_label' => 'Noir / M',
        'sku' => 'SKU-1', 'unit_price' => 8900, 'quantity' => 1, 'total' => 8900,
    ]);

    return $order;
}

it('computes the VAT contained in a tax-inclusive total', function () {
    $order = Order::factory()->make(['total' => 12000]);

    expect($order->vatTotal())->toBe(2000);
});

it('lets the owner download the invoice of a paid order', function () {
    $user = User::factory()->create();
    $order = paidOrderWithItem($user);

    $response = $this->actingAs($user)->get(route('account.orders.invoice', $order));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain(GenerateInvoice::filename($order))
        ->and($response->baseResponse->getContent())->toStartWith('%PDF');
});

it('has no invoice for an unpaid order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $this->actingAs($user)->get(route('account.orders.invoice', $order))->assertNotFound();
});

it('forbids downloading the invoice of another customer', function () {
    $order = paidOrderWithItem();

    $this->actingAs(User::factory()->create())
        ->get(route('account.orders.invoice', $order))
        ->assertForbidden();
});
