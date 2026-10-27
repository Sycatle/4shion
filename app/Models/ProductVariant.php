<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read Product $product
 */
#[Fillable(['product_id', 'sku', 'size', 'color', 'color_hex', 'price_override', 'stock'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_override' => 'integer',
            'stock' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Unit price in cents: the variant override, falling back to the product base price.
     */
    public function price(): int
    {
        return $this->price_override ?? $this->product->base_price;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function label(): string
    {
        return "{$this->color} / {$this->size}";
    }
}
