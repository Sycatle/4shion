<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Livewire\Account\OrderList;
use App\Livewire\Account\OrderShow;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function (): void {
    $this->get(route('account.orders'))->assertRedirect(route('login'));
});

it('lists only the orders of the current customer', function (): void {
    $user = User::factory()->create();
    $mine = Order::factory()->for($user)->create(['number' => '4SH-2026-000001']);
    Order::factory()->create(['number' => '4SH-2026-000002']);

    Livewire::actingAs($user)->test(OrderList::class)
        ->assertSee($mine->number)
        ->assertDontSee('4SH-2026-000002');
});

it('shows the detail of an order', function (): void {
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

it('forbids viewing the order of another customer', function (): void {
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('account.orders.show', $order))
        ->assertForbidden();
});

it('lets an admin view any order', function (): void {
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('account.orders.show', $order))
        ->assertOk();
});

it('redirects the legacy dashboard route to the orders', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertRedirect('/compte/commandes');
});

it('lets the customer pay a pending order again', function (): void {
    $gateway = fakeGateway();
    $user = User::factory()->create();
    $this->actingAs($user);
    $order = pendingOrder($user);

    Livewire::test(OrderShow::class, ['order' => $order])
        ->assertSee('Cette commande attend son paiement')
        ->call('pay')
        ->assertRedirect('https://checkout.test/pay/'.$order->id);

    expect($order->fresh()->stripe_session_id)->not->toBeNull();
});

it('does not offer payment for an order that is not pending', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->paid()->create();

    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])
        ->assertDontSee('Cette commande attend son paiement')
        ->call('pay')
        ->assertNoRedirect();
});
