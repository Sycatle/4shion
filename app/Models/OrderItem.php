<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read Order $order
 */
#[Fillable([
    'order_id', 'product_variant_id', 'product_name', 'variant_label', 'sku', 'unit_price', 'quantity', 'backordered_quantity',
    'is_gift_card', 'total',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'backordered_quantity' => 'integer',
            'is_gift_card' => 'boolean',
            'total' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** @return HasMany<GiftCard, $this> */
    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }

    /** @return HasMany<ShipmentItem, $this> */
    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /** @return HasMany<OrderReturnItem, $this> */
    public function returnItems(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    public function shippedQuantity(): int
    {
        return (int) $this->shipmentItems()->sum('quantity');
    }

    /** Units still to hand to a carrier: in stock for the customer, not shipped yet. Gift cards never ship. */
    public function shippableQuantity(): int
    {
        return $this->is_gift_card ? 0 : max(0, $this->reservedQuantity() - $this->shippedQuantity());
    }

    /** Units the customer can still send back: shipped, and not already part of a live return. */
    public function returnableQuantity(): int
    {
        if ($this->is_gift_card) {
            return 0;
        }

        $returned = (int) $this->returnItems()
            ->whereHas('orderReturn', fn (Builder $returns) => $returns->whereNot('status', ReturnStatus::Rejected))
            ->sum('quantity');

        return max(0, $this->shippedQuantity() - $returned);
    }

    /** Units taken from stock at order time; the rest waits for a restock. */
    public function reservedQuantity(): int
    {
        return $this->quantity - $this->backordered_quantity;
    }
}
