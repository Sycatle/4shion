<?php

declare(strict_types=1);

use App\Actions\Checkout\CreateOrder;
use App\Actions\Orders\CreateShipment;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\MarkOrderRefunded;
use App\Actions\Orders\MarkOrderShipped;
use App\Actions\Orders\RefundOrder;
use App\Actions\Returns\ApproveReturn;
use App\Actions\Returns\ReceiveReturn;
use App\Actions\Returns\RefundReturn;
use App\Actions\Returns\RejectReturn;
use App\Actions\Returns\RequestReturn;
use App\Cart\CartManager;
use App\Enums\Carrier;
use App\Enums\OrderStatus;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Returns\Pages\ManageReturns;
use App\Filament\Resources\ShippingMethods\Pages\ManageShippingMethods;
use App\Livewire\Account\ReturnForm;
use App\Livewire\Shop\CheckoutPage;
use App\Mail\ReturnUpdate;
use App\Mail\ShipmentNotice;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/** Paid order of two lines (2 + 1 units) for a fresh customer. */
function paidOrder(?User $user = null): Order
{
    $user ??= User::factory()->create();
    $a = ProductVariant::factory()->for(Product::factory()->create(['base_price' => 5000]))->create(['stock' => 10]);
    $b = ProductVariant::factory()->for(Product::factory()->create(['base_price' => 3000]))->create(['stock' => 10]);
    app(CartManager::class)->add($a, 2);
    app(CartManager::class)->add($b, 1);
    $order = app(CreateOrder::class)($user, address());
    app(MarkOrderPaid::class)($order, 'pi_test_123');

    return $order->refresh()->load('items');
}

function shippedOrder(?User $user = null): Order
{
    $order = paidOrder($user);
    app(MarkOrderShipped::class)($order);

    return $order->refresh();
}

describe('shipping methods', function (): void {
    it('prices the order with the chosen method and keeps its name', function (): void {
        fakeGateway();
        ShippingMethod::factory()->create(['name' => 'Colissimo', 'price' => 490, 'position' => 1]);
        $express = ShippingMethod::factory()->create(['name' => 'Express', 'price' => 1290, 'free_threshold' => 30000, 'position' => 2]);
        $this->actingAs(User::factory()->create());
        cartWith(price: 12000);

        Livewire::test(CheckoutPage::class)
            ->assertSet('shippingMethodId', ShippingMethod::query()->active()->first()->id)
            ->set('shippingMethodId', $express->id)
            ->set('firstName', 'Camille')->set('lastName', 'Martin')->set('line1', '12 rue des Lilas')->set('postalCode', '75011')->set('city', 'Paris')
            ->set('acceptTerms', true)
            ->call('submit')
            ->assertHasNoErrors();

        $order = Order::query()->sole();
        expect($order->shipping_total)->toBe(1290)->and($order->shipping_method_name)->toBe('Express')->and($order->shipping_method_id)->toBe($express->id);
    });

    it('applies the free threshold of the method', function (): void {
        $method = ShippingMethod::factory()->create(['price' => 600, 'free_threshold' => 5000]);
        cartWith(price: 5000);

        expect(app(CartManager::class)->totalsFor(app(CartManager::class)->items(), null, $method)->shipping)->toBe(0)
            ->and(app(CartManager::class)->totalsFor(app(CartManager::class)->items(), null, ShippingMethod::factory()->create(['price' => 600]))->shipping)->toBe(600);
    });

    it('falls back on the shop-wide rate when no method exists', function (): void {
        cartWith(price: 3000);

        expect(app(CartManager::class)->totals()->shipping)->toBe(490);
    });

    it('is managed from the back-office', function (): void {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ManageShippingMethods::class)
            ->callAction('create', data: ['name' => 'Express', 'carrier' => 'chronopost', 'price' => '12.90', 'free_threshold' => '300', 'is_active' => true])
            ->assertHasNoFormErrors();

        $method = ShippingMethod::sole();
        expect($method->price)->toBe(1290)->and($method->free_threshold)->toBe(30000)->and($method->carrier)->toBe(Carrier::Chronopost);
    });
});

describe('shipments', function (): void {
    it('ships a whole order in one parcel with tracking', function (): void {
        Mail::fake();
        $order = paidOrder();

        expect(app(MarkOrderShipped::class)($order, Carrier::Colissimo, '6A12345'))->toBeTrue();

        $order->refresh();
        $shipment = $order->shipments()->sole();
        expect($order->status)->toBe(OrderStatus::Shipped)
            ->and($shipment->items)->toHaveCount(2)
            ->and($shipment->trackingUrl())->toContain('6A12345');
    });

    it('ships in several parcels and only closes the order with the last one', function (): void {
        Mail::fake();
        $order = paidOrder();
        [$first, $second] = $order->items->all();

        app(CreateShipment::class)($order, [$first->id => 1], Carrier::Colissimo, 'TRACK1');
        expect($order->refresh()->status)->toBe(OrderStatus::Paid);
        Mail::assertQueued(ShipmentNotice::class, 1);

        app(CreateShipment::class)($order, [$first->id => 1, $second->id => 1], Carrier::Colissimo, 'TRACK2');

        expect($order->refresh()->status)->toBe(OrderStatus::Shipped)
            ->and($order->shipments)->toHaveCount(2)
            ->and($first->refresh()->shippedQuantity())->toBe(2);
    });

    it('refuses to ship more than what is left', function (): void {
        $order = paidOrder();
        $item = $order->items->first();

        expect(fn () => app(CreateShipment::class)($order, [$item->id => 5]))->toThrow(InvalidArgumentException::class)
            ->and(fn () => app(CreateShipment::class)($order, [$item->id => 0]))->toThrow(InvalidArgumentException::class, 'Aucun article');
    });

    it('does not ship an unpaid order', function (): void {
        $order = pendingOrder(User::factory()->create());

        expect(fn () => app(CreateShipment::class)($order, [$order->items->first()->id => 1]))->toThrow(InvalidArgumentException::class)
            ->and(app(MarkOrderShipped::class)($order))->toBeFalse();
    });

    it('records a partial shipment from the back-office', function (): void {
        Mail::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $order = paidOrder();
        $item = $order->items->first();

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('partialShip', data: [
                'lines' => [['id' => $item->id, 'max' => 2, 'quantity' => 1]],
                'carrier' => 'dhl',
                'tracking_number' => 'JD0123',
            ])
            ->assertNotified('Colis enregistré');

        expect($order->shipments()->sole()->carrier)->toBe(Carrier::Dhl)
            ->and($order->refresh()->status)->toBe(OrderStatus::Paid);
    });

    it('shows tracking on the customer order page', function (): void {
        Mail::fake();
        $user = User::factory()->create();
        $order = paidOrder($user);
        app(MarkOrderShipped::class)($order, Carrier::Colissimo, 'TRACKME');
        $this->actingAs($user);

        $this->get(route('account.orders.show', $order))->assertOk()->assertSee('TRACKME')->assertSee('Suivre le colis');
    });
});

describe('returns', function (): void {
    function returnFor(Order $order, int $quantity = 1): OrderReturn
    {
        return app(RequestReturn::class)($order, [$order->items->first()->id => $quantity], ReturnReason::WrongSize, 'Trop petit');
    }

    it('lets a customer request a return within the window and warns them by mail', function (): void {
        Mail::fake();
        $user = User::factory()->create();
        $order = shippedOrder($user);
        $this->actingAs($user);

        Livewire::test(ReturnForm::class, ['order' => $order])
            ->set('quantities', [$order->items->first()->id => 1])
            ->set('reason', 'wrong_size')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('account.orders.show', $order));

        $return = OrderReturn::sole();
        expect($return->status)->toBe(ReturnStatus::Requested)->and($return->number)->toStartWith('RET-');
        Mail::assertQueued(ReturnUpdate::class);
    });

    it('cannot return more than was shipped or twice the same units', function (): void {
        Mail::fake();
        $order = shippedOrder();
        $item = $order->items->first();

        returnFor($order, 2);

        expect(fn (): OrderReturn => returnFor($order, 1))->toThrow(InvalidArgumentException::class, 'trop élevée')
            ->and($item->refresh()->returnableQuantity())->toBe(0);
    });

    it('frees the units again when a return is rejected', function (): void {
        Mail::fake();
        $order = shippedOrder();
        $return = returnFor($order, 2);

        app(RejectReturn::class)($return, 'Article porté');

        expect($order->items->first()->refresh()->returnableQuantity())->toBe(2)
            ->and($return->refresh()->decision_note)->toBe('Article porté');
    });

    it('is closed after the return window', function (): void {
        $order = shippedOrder();
        $order->update(['shipped_at' => now()->subDays(31)]);

        expect($order->refresh()->isReturnable())->toBeFalse()
            ->and(fn (): OrderReturn => returnFor($order))->toThrow(InvalidArgumentException::class, 'plus éligible');
    });

    it('does not offer a return on an order that has not shipped', function (): void {
        $user = User::factory()->create();
        $order = paidOrder($user);
        $this->actingAs($user);

        expect($order->isReturnable())->toBeFalse();
        $this->get(route('account.orders.return', $order))->assertNotFound();
    });

    it('forbids another customer from returning the order', function (): void {
        $order = shippedOrder();
        $this->actingAs(User::factory()->create());

        $this->get(route('account.orders.return', $order))->assertForbidden();
    });

    it('walks through approval, reception with restock and a partial card refund', function (): void {
        Mail::fake();
        $gateway = fakeGateway();
        $order = shippedOrder();
        $item = $order->items->first();
        $variant = $item->variant;
        $stockBefore = $variant->refresh()->stock;
        $return = returnFor($order, 1);

        app(ApproveReturn::class)($return);
        app(ReceiveReturn::class)($return, restock: true);
        expect($variant->refresh()->stock)->toBe($stockBefore + 1);

        app(RefundReturn::class)($return);

        $return->refresh();
        expect($return->status)->toBe(ReturnStatus::Refunded)
            ->and($return->refund_amount)->toBe(5000)
            ->and($order->refresh()->refunded_total)->toBe(5000)
            ->and($gateway->partialRefunds)->toBe([['intent' => 'pi_test_123', 'amount' => 5000, 'key' => "return-{$return->id}"]]);
    });

    it('shares the order discount across the refunded goods', function (): void {
        $order = shippedOrder();
        $order->update(['discount_total' => 800, 'total' => $order->total - 800]);
        $return = returnFor($order->refresh(), 1);

        // 5000 * (13000 - 800) / 13000
        expect($return->goodsValue())->toBe(4692);
    });

    it('never refunds more than the card was charged', function (): void {
        Mail::fake();
        fakeGateway();
        $order = shippedOrder();
        $return = returnFor($order, 2);
        app(ApproveReturn::class)($return);
        app(ReceiveReturn::class)($return, restock: false);

        expect(fn () => app(RefundReturn::class)($return, $order->amountDue() + 1))->toThrow(InvalidArgumentException::class, 'Montant')
            ->and($order->refresh()->refunded_total)->toBe(0)
            ->and($return->refresh()->status)->toBe(ReturnStatus::Received);
    });

    it('keeps the return refundable when the payment provider fails', function (): void {
        Mail::fake();
        $gateway = fakeGateway();
        $order = shippedOrder();
        $return = returnFor($order);
        app(ApproveReturn::class)($return);
        app(ReceiveReturn::class)($return, restock: false);
        $gateway->fails = true;

        expect(fn () => app(RefundReturn::class)($return))->toThrow(RuntimeException::class)
            ->and($return->refresh()->status)->toBe(ReturnStatus::Received)
            ->and($order->refresh()->refunded_total)->toBe(0);
    });

    it('rejects out-of-order steps', function (): void {
        Mail::fake();
        $return = returnFor(shippedOrder());

        expect(fn () => app(ReceiveReturn::class)($return, restock: true))->toThrow(InvalidArgumentException::class)
            ->and(fn () => app(RefundReturn::class)($return))->toThrow(InvalidArgumentException::class);
    });

    it('blocks a full refund after a partial one, and ignores the webhook that follows', function (): void {
        Mail::fake();
        fakeGateway();
        $order = shippedOrder();
        $return = returnFor($order);
        app(ApproveReturn::class)($return);
        app(ReceiveReturn::class)($return, restock: false);
        app(RefundReturn::class)($return);

        expect(fn () => app(RefundOrder::class)($order->refresh()))->toThrow(LogicException::class)
            ->and(app(MarkOrderRefunded::class)($order))->toBeFalse()
            ->and($order->refresh()->status)->toBe(OrderStatus::Shipped);
    });

    it('serves the credit note to its owner once refunded', function (): void {
        Mail::fake();
        fakeGateway();
        $user = User::factory()->create();
        $order = shippedOrder($user);
        $return = returnFor($order);
        $this->actingAs($user);

        $this->get(route('account.returns.credit-note', [$order, $return]))->assertNotFound();

        app(ApproveReturn::class)($return);
        app(ReceiveReturn::class)($return, restock: false);
        app(RefundReturn::class)($return);

        $this->get(route('account.returns.credit-note', [$order, $return]))->assertOk()->assertHeader('content-type', 'application/pdf');
    });

    it('is handled from the back-office', function (): void {
        Mail::fake();
        fakeGateway();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $return = returnFor(shippedOrder());

        Livewire::test(ManageReturns::class)
            ->assertCanSeeTableRecords([$return])
            ->callAction(TestAction::make('approve')->table($return))
            ->callAction(TestAction::make('receive')->table($return), data: ['restock' => true])
            ->callAction(TestAction::make('refund')->table($return), data: ['amount' => '50.00'])
            ->assertNotified('Remboursement effectué');

        expect($return->refresh()->status)->toBe(ReturnStatus::Refunded);
    });
});
