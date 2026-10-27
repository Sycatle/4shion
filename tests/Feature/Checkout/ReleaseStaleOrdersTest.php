<?php

use App\Enums\OrderStatus;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;

it('cancels abandoned pending orders and releases their stock', function () {
    fakeGateway();
    $stale = pendingOrder(User::factory()->create(), quantity: 2);
    $stale->forceFill(['created_at' => now()->subHours(2)])->save();
    $fresh = pendingOrder(User::factory()->create());

    $this->artisan('orders:release-stale')
        ->expectsOutputToContain('Released 1 stale order(s).')
        ->assertSuccessful();

    expect($stale->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($stale->items->first()->variant->fresh()->stock)->toBe(10)
        ->and($fresh->fresh()->status)->toBe(OrderStatus::Pending);
});

it('is scheduled', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('orders:release-stale');
});

it('lets the database reject negative stock', function () {
    $variant = ProductVariant::factory()->create(['stock' => 1]);

    expect(fn () => $variant->decrement('stock', 2))->toThrow(QueryException::class);
});
