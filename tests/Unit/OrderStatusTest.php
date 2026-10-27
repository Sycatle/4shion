<?php

use App\Enums\OrderStatus;

it('allows only the documented lifecycle moves', function (OrderStatus $from, OrderStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'pending to paid' => [OrderStatus::Pending, OrderStatus::Paid, true],
    'pending to cancelled' => [OrderStatus::Pending, OrderStatus::Cancelled, true],
    'pending to shipped' => [OrderStatus::Pending, OrderStatus::Shipped, false],
    'paid to shipped' => [OrderStatus::Paid, OrderStatus::Shipped, true],
    'paid to cancelled' => [OrderStatus::Paid, OrderStatus::Cancelled, false],
    'shipped to delivered' => [OrderStatus::Shipped, OrderStatus::Delivered, true],
    'delivered to refunded' => [OrderStatus::Delivered, OrderStatus::Refunded, true],
    'paid to paid' => [OrderStatus::Paid, OrderStatus::Paid, false],
]);

it('treats cancelled and refunded as terminal', function (OrderStatus $status) {
    expect($status->transitions())->toBeEmpty();
})->with([OrderStatus::Cancelled, OrderStatus::Refunded]);
