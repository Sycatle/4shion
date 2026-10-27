<?php

declare(strict_types=1);

use App\Actions\Checkout\CreateOrder;
use App\Actions\Checkout\StartCheckout;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Cart\CartManager;
use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Exceptions\Orders\InvalidOrderTransition;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('starts a checkout and returns the payment url', function () {
    fakeGateway();
    $user = User::factory()->create();
    cartWith();

    $url = app(StartCheckout::class)($user, address());

    $order = $user->orders()->sole();
    expect($url)->toBe("https://checkout.test/pay/{$order->id}")
        ->and($order->stripe_session_id)->toBe("cs_test_{$order->id}");
});

it('cancels the order, releases the stock and restores the cart when the provider fails', function () {
    $gateway = fakeGateway();
    $gateway->fails = true;
    $user = User::factory()->create();
    $variant = cartWith(stock: 5, quantity: 2);

    expect(fn () => app(StartCheckout::class)($user, address()))->toThrow(RuntimeException::class);

    expect($user->orders()->sole()->status)->toBe(OrderStatus::Cancelled)
        ->and($variant->fresh()->stock)->toBe(5)
        ->and(app(CartManager::class)->count())->toBe(2);
});

it('cancels a pending order once and closes the payment session', function () {
    $gateway = fakeGateway();
    $user = User::factory()->create();
    $variant = cartWith(stock: 5, quantity: 2);
    app(StartCheckout::class)($user, address());
    $order = $user->orders()->sole();

    app(CancelOrder::class)($order);
    app(CancelOrder::class)($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($variant->fresh()->stock)->toBe(5)
        ->and($gateway->expired)->toBe(["cs_test_{$order->id}"]);
});

it('does not cancel a paid order', function () {
    fakeGateway();
    $order = pendingOrder(User::factory()->create());
    app(MarkOrderPaid::class)($order);

    app(CancelOrder::class)($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('marks an order as paid only once', function () {
    Event::fake([OrderPaid::class]);
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create(['code' => 'BIENVENUE10']);
    cartWith(price: 10000);
    app(CartManager::class)->applyCoupon('BIENVENUE10');
    $order = (new CreateOrder(app(CartManager::class)))($user, address());

    $first = app(MarkOrderPaid::class)($order, 'pi_123');
    $second = app(MarkOrderPaid::class)($order, 'pi_123');

    expect($first)->toBeTrue()
        ->and($second)->toBeFalse()
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->stripe_payment_intent)->toBe('pi_123')
        ->and($order->fresh()->paid_at)->not->toBeNull()
        ->and($coupon->fresh()->used_count)->toBe(1);
    Event::assertDispatchedTimes(OrderPaid::class, 1);
});

it('refuses a transition outside the order lifecycle', function () {
    $order = Order::factory()->create();

    expect(fn () => $order->transitionTo(OrderStatus::Shipped))->toThrow(InvalidOrderTransition::class)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending);
});
