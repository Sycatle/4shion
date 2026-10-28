<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;

/**
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read Product $product
 */
#[Fillable([
    'product_id', 'sku', 'size', 'color', 'color_hex', 'price_override', 'stock',
    'low_stock_threshold', 'preorder_enabled', 'preorder_available_on', 'preorder_limit',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, LogsActivity;

    /** Gift cards are not stocked: this stands for "as many as requested". */
    public const int UNLIMITED = PHP_INT_MAX;

    protected function casts(): array
    {
        return [
            'price_override' => 'integer',
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'preorder_enabled' => 'boolean',
            'preorder_available_on' => 'date',
            'preorder_limit' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @return HasMany<StockAlert, $this> */
    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Price before any sale, in cents: the variant override, falling back to the product base price. */
    public function regularPrice(): int
    {
        return $this->price_override ?? $this->product->base_price;
    }

    /** Price actually charged, in cents. */
    public function price(): int
    {
        return $this->product->salePriceOf($this->regularPrice());
    }

    public function isOnSale(): bool
    {
        return $this->price() < $this->regularPrice();
    }

    public function isInStock(): bool
    {
        return $this->product->is_gift_card || $this->stock > 0;
    }

    public function lowStockThreshold(): int
    {
        return $this->low_stock_threshold ?? Config::integer('shop.low_stock_threshold');
    }

    public function isLowStock(): bool
    {
        return ! $this->product->is_gift_card && $this->stock > 0 && $this->stock <= $this->lowStockThreshold();
    }

    /** Units already pre-ordered and waiting for a restock. */
    public function backorderedQuantity(): int
    {
        return (int) $this->orderItems()->sum('backordered_quantity');
    }

    /** Units that can still be pre-ordered, or UNLIMITED when there is no cap. */
    public function preorderRemaining(): int
    {
        if (! $this->preorder_enabled) {
            return 0;
        }

        return $this->preorder_limit === null
            ? self::UNLIMITED
            : max(0, $this->preorder_limit - $this->backorderedQuantity());
    }

    /** Sold out but open to pre-orders: the customer can still buy it. */
    public function isPreorderable(): bool
    {
        return ! $this->product->is_gift_card && $this->stock === 0 && $this->preorderRemaining() > 0;
    }

    /** Units a customer can order right now: stock plus pre-order allowance. */
    public function availableQuantity(): int
    {
        if ($this->product->is_gift_card) {
            return self::UNLIMITED;
        }

        $preorder = $this->preorderRemaining();

        return $preorder === self::UNLIMITED ? self::UNLIMITED : $this->stock + $preorder;
    }

    public function canBeOrdered(): bool
    {
        return $this->availableQuantity() > 0;
    }

    public function label(): string
    {
        return "{$this->color} / {$this->size}";
    }
}
