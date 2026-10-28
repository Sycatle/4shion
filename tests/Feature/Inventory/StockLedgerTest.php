<?php

declare(strict_types=1);

use App\Actions\Checkout\CreateOrder;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\MarkOrderShipped;
use App\Cart\CartManager;
use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\Cart\CartException;
use App\Inventory\StockLedger;
use App\Mail\BackInStockNotice;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function preorderVariant(int $stock = 0, ?int $limit = null): ProductVariant
{
    return ProductVariant::factory()
        ->for(Product::factory()->create(['base_price' => 5000]))
        ->create(['stock' => $stock, 'preorder_enabled' => true, 'preorder_limit' => $limit]);
}

it('records every stock change with its reason and resulting stock', function (): void {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $admin = User::factory()->create(['is_admin' => true]);
    $ledger = app(StockLedger::class);

    $ledger->add($variant, 10, StockMovementReason::Restock, user: $admin, note: 'Livraison fournisseur');
    $ledger->remove($variant, 3, StockMovementReason::Adjustment, user: $admin);

    expect($variant->fresh()->stock)->toBe(12)
        ->and(StockMovement::query()->orderBy('id')->get()->map(fn (StockMovement $m): array => [$m->reason, $m->quantity, $m->stock_after])->all())
        ->toBe([[StockMovementReason::Restock, 10, 15], [StockMovementReason::Adjustment, -3, 12]]);
});

it('refuses to take out more than the stock', function (): void {
    $variant = ProductVariant::factory()->create(['stock' => 2]);

    app(StockLedger::class)->remove($variant, 3, StockMovementReason::Adjustment);
})->throws(InvalidArgumentException::class);

it('logs the sale and the cancellation of an order', function (): void {
    $order = pendingOrder(User::factory()->create(), quantity: 2);
    $variant = $order->items->sole()->variant;

    expect(StockMovement::query()->sole())->reason->toBe(StockMovementReason::Sale)
        ->and($variant->fresh()->stock)->toBe(8);

    fakeGateway();
    app(CancelOrder::class)($order);

    expect($variant->fresh()->stock)->toBe(10)
        ->and(StockMovement::query()->orderByDesc('id')->first())->reason->toBe(StockMovementReason::Cancellation);
});

it('lets a sold-out variant be pre-ordered and ships the backordered units after a restock', function (): void {
    $user = User::factory()->create();
    $variant = preorderVariant(stock: 1);
    app(CartManager::class)->add($variant, 3);

    $order = app(CreateOrder::class)($user, address());
    $item = $order->items->sole();

    expect($item->quantity)->toBe(3)
        ->and($item->backordered_quantity)->toBe(2)
        ->and($variant->fresh()->stock)->toBe(0)
        ->and($order->hasBackorders())->toBeTrue();

    app(MarkOrderPaid::class)($order);
    expect(app(MarkOrderShipped::class)($order->fresh()))->toBeFalse();

    app(StockLedger::class)->add($variant, 5, StockMovementReason::Restock);

    expect($item->fresh()->backordered_quantity)->toBe(0)
        ->and($variant->fresh()->stock)->toBe(3)
        ->and(StockMovement::query()->where('reason', StockMovementReason::PreorderAllocation)->sole()->quantity)->toBe(-2)
        ->and(app(MarkOrderShipped::class)($order->fresh()))->toBeTrue();
});

it('serves pre-orders oldest first', function (): void {
    $variant = preorderVariant();
    $first = User::factory()->create();
    $second = User::factory()->create();

    app(CartManager::class)->add($variant, 2);
    $firstOrder = app(CreateOrder::class)($first, address());
    app(CartManager::class)->add($variant, 2);
    $secondOrder = app(CreateOrder::class)($second, address());

    app(StockLedger::class)->add($variant, 3, StockMovementReason::Restock);

    expect($firstOrder->items->sole()->fresh()->backordered_quantity)->toBe(0)
        ->and($secondOrder->items->sole()->fresh()->backordered_quantity)->toBe(1);
});

it('caps pre-orders at the configured limit', function (): void {
    $variant = preorderVariant(limit: 2);

    app(CartManager::class)->add($variant, 3);
})->throws(CartException::class, 'seulement 2');

it('drops the pre-orders of a cancelled order so the next restock is not wasted on it', function (): void {
    fakeGateway();
    $variant = preorderVariant();
    app(CartManager::class)->add($variant, 2);
    $order = app(CreateOrder::class)(User::factory()->create(), address());

    app(CancelOrder::class)($order);
    app(StockLedger::class)->add($variant, 4, StockMovementReason::Restock);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($variant->fresh()->stock)->toBe(4);
});

it('emails the customers waiting for a variant once, when it is back in stock', function (): void {
    Mail::fake();
    $variant = ProductVariant::factory()->outOfStock()->create();
    StockAlert::create(['product_variant_id' => $variant->id, 'email' => 'attente@example.test']);
    $ledger = app(StockLedger::class);

    $ledger->add($variant, 5, StockMovementReason::Restock);
    $ledger->add($variant, 5, StockMovementReason::Restock);

    Mail::assertQueued(BackInStockNotice::class, 1);
    expect(StockAlert::query()->sole()->notified_at)->not->toBeNull();
});

it('does not alert when a variant that still had stock is topped up', function (): void {
    Mail::fake();
    $variant = ProductVariant::factory()->create(['stock' => 2]);
    StockAlert::create(['product_variant_id' => $variant->id, 'email' => 'attente@example.test']);

    app(StockLedger::class)->add($variant, 5, StockMovementReason::Restock);

    Mail::assertNothingQueued();
});
