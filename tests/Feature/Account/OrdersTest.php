<?php

use App\Enums\OrderStatus;
use App\Livewire\Account\OrderList;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get(route('account.orders'))->assertRedirect(route('login'));
});

it('lists only the orders of the current customer', function () {
    $user = User::factory()->create();
    $mine = Order::factory()->for($user)->create(['number' => '4SH-2026-000001']);
    Order::factory()->create(['number' => '4SH-2026-000002']);

    Livewire::actingAs($user)->test(OrderList::class)
        ->assertSee($mine->number)
        ->assertDontSee('4SH-2026-000002');
});

it('shows the detail of an order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->paid()->create(['number' => '4SH-2026-000010']);
    OrderItem::create([
        'order_id' => $order->id, 'product_name' => 'Hoodie Atlas', 'variant_label' => 'Noir / M',
        'sku' => 'SKU-1', 'unit_price' => 8900, 'quantity' => 1, 'total' => 8900,
    ]);

    $this->actingAs($user)->get(route('account.orders.show', $order))
        ->assertOk()
        ->assertSee('Hoodie Atlas')
        ->assertSee(OrderStatus::Paid->label());
});

it('forbids viewing the order of another customer', function () {
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('account.orders.show', $order))
        ->assertForbidden();
});

it('lets an admin view any order', function () {
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('account.orders.show', $order))
        ->assertOk();
});

it('redirects the legacy dashboard route to the orders', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertRedirect('/compte/commandes');
});
