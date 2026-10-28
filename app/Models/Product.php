<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read Category $category
 */
#[Fillable([
    'category_id', 'name', 'slug', 'description', 'specs', 'base_price', 'is_active', 'is_featured', 'is_gift_card',
    'sale_percent', 'sale_starts_at', 'sale_ends_at',
])]
#[RouteKey('slug')]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity, Searchable;

    public const IMAGES = 'images';

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'base_price' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_gift_card' => 'boolean',
            'sale_percent' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
        ];
    }

    /** Percentage off while a sale is running, null otherwise. */
    public function activeSalePercent(): ?int
    {
        if ($this->sale_percent === null || $this->sale_starts_at?->isFuture() || $this->sale_ends_at?->isPast()) {
            return null;
        }

        return $this->sale_percent;
    }

    /** Price after the running sale, in cents. */
    public function salePriceOf(int $regularPrice): int
    {
        $percent = $this->activeSalePercent();

        return $percent === null ? $regularPrice : (int) round($regularPrice * (100 - $percent) / 100);
    }

    public function price(): int
    {
        return $this->salePriceOf($this->base_price);
    }

    /** @return array<string, string> label => value, only the specs that are filled in */
    public function specSheet(): array
    {
        $labels = ['composition' => 'Composition', 'weight' => 'Grammage', 'fit' => 'Coupe', 'care' => 'Entretien'];

        $sheet = [];

        foreach ($labels as $key => $label) {
            $value = $this->specs[$key] ?? null;

            if (is_string($value) && $value !== '') {
                $sheet[$label] = $value;
            }
        }

        return $sheet;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category->name,
            'is_active' => $this->is_active,
            'base_price' => $this->base_price,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::IMAGES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->nonQueued()
            ->width(640)
            ->height(800)
            ->format('webp');

        $this->addMediaConversion('detail')
            ->nonQueued()
            ->width(1200)
            ->height(1500)
            ->format('webp');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Adds `reviews_avg_rating` and `reviews_count` over published reviews, for product cards.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    #[Scope]
    protected function withRating(Builder $query): Builder
    {
        $published = fn (Builder $reviews): Builder => $reviews->whereNotNull('approved_at');

        return $query
            ->withAvg(['reviews' => $published], 'rating')
            ->withCount(['reviews' => $published]);
    }

    public function imageUrl(string $conversion = 'card'): ?string
    {
        $media = $this->getFirstMedia(self::IMAGES);

        if (! $media instanceof Media) {
            return null;
        }

        return $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }
}
