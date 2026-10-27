<?php

declare(strict_types=1);

use App\Actions\Orders\MarkOrderPaid;
use App\Enums\OrderStatus;
use App\Jobs\ProcessStripeEvent;
use App\Models\Order;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

function webhook(string $type, array $object, ?string $secret = 'whsec_test', ?string $eventId = null): TestResponse
{
    config(['cashier.webhook.secret' => 'whsec_test']);

    $payload = json_encode(['id' => $eventId ?? 'evt_'.uniqid(), 'type' => $type, 'data' => ['object' => $object]]);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", (string) $secret);

    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
    ], $payload);
}

function orderWithSession(): Order
{
    fakeGateway();
    $order = pendingOrder(User::factory()->create());
    $order->update(['stripe_session_id' => 'cs_live_1']);

    return $order;
}

it('rejects webhooks with an invalid signature', function (): void {
    webhook('checkout.session.completed', ['id' => 'cs_live_1'], secret: 'wrong')->assertForbidden();
});

it('marks the order as paid on checkout.session.completed', function (): void {
    $order = orderWithSession();

    webhook('checkout.session.completed', [
        'id' => 'cs_live_1',
        'payment_status' => 'paid',
        'amount_total' => $order->total,
        'payment_intent' => 'pi_abc',
    ])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->stripe_payment_intent)->toBe('pi_abc');
});

it('is idempotent when Stripe redelivers the event', function (): void {
    $order = orderWithSession();
    $session = ['id' => 'cs_live_1', 'payment_status' => 'paid', 'amount_total' => $order->total, 'payment_intent' => 'pi_abc'];

    webhook('checkout.session.completed', $session)->assertOk();
    $paidAt = $order->fresh()->paid_at;
    webhook('checkout.session.completed', $session)->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->paid_at->equalTo($paidAt))->toBeTrue();
});

it('processes a redelivered event only once', function (): void {
    Queue::fake();
    $session = ['id' => 'cs_live_1', 'payment_status' => 'paid', 'amount_total' => 100, 'payment_intent' => 'pi_abc'];

    webhook('checkout.session.completed', $session, eventId: 'evt_same')->assertOk();
    webhook('checkout.session.completed', $session, eventId: 'evt_same')->assertOk();

    Queue::assertPushed(ProcessStripeEvent::class, 1);
    expect(StripeEvent::query()->find('evt_same')?->type)->toBe('checkout.session.completed');
});

it('marks the event as processed once handled', function (): void {
    $order = orderWithSession();

    webhook('checkout.session.completed', [
        'id' => 'cs_live_1', 'payment_status' => 'paid', 'amount_total' => $order->total, 'payment_intent' => 'pi_abc',
    ], eventId: 'evt_done')->assertOk();

    expect(StripeEvent::query()->find('evt_done')?->processed_at)->not->toBeNull();
});

it('ignores a payment whose amount does not match the order', function (): void {
    $order = orderWithSession();

    webhook('checkout.session.completed', ['id' => 'cs_live_1', 'payment_status' => 'paid', 'amount_total' => 1, 'payment_intent' => 'pi_abc'])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('does not mark unpaid sessions as paid', function (): void {
    $order = orderWithSession();

    webhook('checkout.session.completed', ['id' => 'cs_live_1', 'payment_status' => 'unpaid', 'amount_total' => $order->total])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('cancels the order and releases the stock when the session expires', function (): void {
    $order = orderWithSession();
    $variant = $order->items->first()->variant;
    $stockAfterReservation = $variant->fresh()->stock;

    webhook('checkout.session.expired', ['id' => 'cs_live_1'])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($variant->fresh()->stock)->toBe($stockAfterReservation + 1);
});

it('marks a fully refunded order as refunded', function (): void {
    $order = orderWithSession();
    app(MarkOrderPaid::class)($order, 'pi_abc');

    webhook('charge.refunded', ['payment_intent' => 'pi_abc', 'refunded' => true])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
});
