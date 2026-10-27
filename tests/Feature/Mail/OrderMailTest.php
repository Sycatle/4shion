<?php

declare(strict_types=1);

use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\MarkOrderShipped;
use App\Mail\OrderConfirmation;
use App\Mail\OrderShippedNotice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('queues a confirmation email when an order is paid', function () {
    $user = User::factory()->create();
    $order = pendingOrder($user);

    app(MarkOrderPaid::class)($order, 'pi_test_123');

    Mail::assertQueued(OrderConfirmation::class, fn (OrderConfirmation $mail) => $mail->hasTo($user->email) && $mail->order->is($order));
});

it('sends the confirmation only once when payment is reported twice', function () {
    $order = pendingOrder(User::factory()->create());

    app(MarkOrderPaid::class)($order);
    app(MarkOrderPaid::class)($order);

    Mail::assertQueued(OrderConfirmation::class, 1);
});

it('renders the confirmation email', function () {
    $order = pendingOrder(User::factory()->create());

    (new OrderConfirmation($order))->assertSeeInHtml($order->number)->assertSeeInHtml('Total TTC');
});

it('attaches the invoice PDF to the confirmation', function () {
    $order = pendingOrder(User::factory()->create());

    $attachments = (new OrderConfirmation($order))->attachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0]->as)->toBe("facture-{$order->number}.pdf")
        ->and($attachments[0]->mime)->toBe('application/pdf');
});

it('queues a shipping email when an order is shipped', function () {
    $order = Order::factory()->paid()->create();

    app(MarkOrderShipped::class)($order);

    Mail::assertQueued(OrderShippedNotice::class, fn (OrderShippedNotice $mail) => $mail->hasTo($order->user->email));
});

it('renders the shipping email in French', function () {
    $order = Order::factory()->paid()->create();

    (new OrderShippedNotice($order))->assertSeeInHtml('en route')->assertSeeInHtml($order->number);
});
