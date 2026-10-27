<?php

declare(strict_types=1);

use App\Actions\Orders\MarkOrderRefunded;
use App\Enums\OrderStatus;
use App\Events\OrderShipped;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

function adminOrder(OrderStatus $status = OrderStatus::Paid, int $stock = 5, int $quantity = 2): Order
{
    $variant = ProductVariant::factory()->create(['stock' => $stock]);
    $order = Order::factory()->paid()->create(['status' => $status, 'stripe_payment_intent' => 'pi_test_123']);
    $order->items()->create([
        'product_variant_id' => $variant->id,
        'product_name' => 'T-shirt',
        'variant_label' => 'M / Noir',
        'sku' => $variant->sku,
        'unit_price' => 4900,
        'quantity' => $quantity,
        'total' => 4900 * $quantity,
    ]);

    return $order;
}

it('lists orders and filters them by status', function (): void {
    $paid = Order::factory()->paid()->create();
    $pending = Order::factory()->create();

    Livewire::test(ListOrders::class)
        ->assertCanSeeTableRecords([$paid, $pending])
        ->filterTable('status', OrderStatus::Paid->value)
        ->assertCanSeeTableRecords([$paid])
        ->assertCanNotSeeTableRecords([$pending]);
});

it('displays an order', function (): void {
    $order = adminOrder();

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSuccessful()
        ->assertSee($order->number);
});

it('ships a paid order and dispatches an event', function (): void {
    Event::fake([OrderShipped::class]);
    $order = adminOrder();

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->callAction('ship');

    expect($order->fresh())->status->toBe(OrderStatus::Shipped)->shipped_at->not->toBeNull();
    Event::assertDispatched(OrderShipped::class);
});

it('only offers delivery for shipped orders', function (): void {
    $paid = adminOrder();
    $shipped = adminOrder(OrderStatus::Shipped);

    Livewire::test(ViewOrder::class, ['record' => $paid->id])->assertActionHidden('deliver');
    Livewire::test(ViewOrder::class, ['record' => $shipped->id])
        ->assertActionVisible('deliver')
        ->callAction('deliver');

    expect($shipped->fresh()->status)->toBe(OrderStatus::Delivered);
});

it('refunds a paid order and restocks the items', function (): void {
    $gateway = fakeGateway();
    $order = adminOrder(stock: 5, quantity: 2);

    Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('refund');

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($gateway->refunded)->toBe(['pi_test_123'])
        ->and($order->items->first()->variant->fresh()->stock)->toBe(7);
});

it('keeps the order untouched when the refund fails', function (): void {
    $gateway = fakeGateway();
    $gateway->fails = true;
    $order = adminOrder(stock: 5, quantity: 2);

    Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('refund');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->items->first()->variant->fresh()->stock)->toBe(5);
});

it('does not offer a refund for pending orders', function (): void {
    $order = Order::factory()->create();

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertActionHidden('refund')
        ->assertActionHidden('ship');
});

it('does not restock twice when the refund webhook follows', function (): void {
    fakeGateway();
    $order = adminOrder(stock: 5, quantity: 2);

    Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('refund');
    (app(MarkOrderRefunded::class))($order);

    expect($order->items->first()->variant->fresh()->stock)->toBe(7);
});
