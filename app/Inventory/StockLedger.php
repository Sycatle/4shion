<?php

declare(strict_types=1);

namespace App\Inventory;

use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Events\VariantRestocked;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Single entry point for stock changes: every change is written to `stock_movements`
 * with its reason, and incoming stock serves outstanding pre-orders first (oldest first).
 */
final readonly class StockLedger
{
    /**
     * Put units into stock: restock, cancelled or refunded order, positive adjustment.
     */
    public function add(
        ProductVariant $variant,
        int $quantity,
        StockMovementReason $reason,
        ?Order $order = null,
        ?User $user = null,
        ?string $note = null,
    ): void {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive.');
        }

        DB::transaction(function () use ($variant, $quantity, $reason, $order, $user, $note): void {
            $locked = $this->lock($variant);
            $wasSoldOut = $locked->stock === 0;
            $stock = $locked->stock + $quantity;

            $this->record($locked, $quantity, $stock, $reason, $order?->id, $user, $note);

            $backorders = OrderItem::query()
                ->where('product_variant_id', $locked->id)
                ->where('backordered_quantity', '>', 0)
                ->whereHas('order', fn ($query) => $query->whereIn('status', [OrderStatus::Pending, OrderStatus::Paid]))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($backorders as $item) {
                if ($stock === 0) {
                    break;
                }

                $allocated = min($stock, $item->backordered_quantity);
                $item->decrement('backordered_quantity', $allocated);
                $stock -= $allocated;

                $this->record($locked, -$allocated, $stock, StockMovementReason::PreorderAllocation, $item->order_id, $user, null);
            }

            $this->save($variant, $locked, $stock);

            if ($wasSoldOut && $stock > 0) {
                event(new VariantRestocked($locked));
            }
        });
    }

    /**
     * Take units out of stock: sale or negative adjustment. The database rejects a negative stock.
     */
    public function remove(
        ProductVariant $variant,
        int $quantity,
        StockMovementReason $reason,
        ?Order $order = null,
        ?User $user = null,
        ?string $note = null,
    ): void {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive.');
        }

        DB::transaction(function () use ($variant, $quantity, $reason, $order, $user, $note): void {
            $locked = $this->lock($variant);

            if ($locked->stock < $quantity) {
                throw new InvalidArgumentException("Only {$locked->stock} unit(s) of {$locked->sku} in stock.");
            }

            $stock = $locked->stock - $quantity;

            $this->record($locked, -$quantity, $stock, $reason, $order?->id, $user, $note);
            $this->save($variant, $locked, $stock);
        });
    }

    /** Manual correction from the back office, positive or negative. */
    public function adjust(ProductVariant $variant, int $delta, StockMovementReason $reason, User $user, ?string $note = null): void
    {
        match (true) {
            $delta > 0 => $this->add($variant, $delta, $reason, user: $user, note: $note),
            $delta < 0 => $this->remove($variant, -$delta, $reason, user: $user, note: $note),
            default => null,
        };
    }

    private function lock(ProductVariant $variant): ProductVariant
    {
        return ProductVariant::query()->with('product')->lockForUpdate()->findOrFail($variant->id);
    }

    private function save(ProductVariant $variant, ProductVariant $locked, int $stock): void
    {
        $locked->update(['stock' => $stock]);

        // Keep the caller's instance in sync with the database.
        $variant->forceFill(['stock' => $stock])->syncOriginalAttribute('stock');
    }

    private function record(
        ProductVariant $variant,
        int $quantity,
        int $stockAfter,
        StockMovementReason $reason,
        ?int $orderId,
        ?User $user,
        ?string $note,
    ): void {
        StockMovement::create([
            'product_variant_id' => $variant->id,
            'order_id' => $orderId,
            'user_id' => $user?->id,
            'reason' => $reason,
            'quantity' => $quantity,
            'stock_after' => $stockAfter,
            'note' => $note,
        ]);
    }
}
