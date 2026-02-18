<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['category_id', 'name', 'slug', 'description', 'specs', 'base_price', 'is_active', 'is_featured'])]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, InteractsWithMedia, Searchable;

    public const IMAGES = 'images';

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'base_price' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /** @return array<string, string> label => value, only the specs that are filled in */
    public function specSheet(): array
    {
        $labels = ['composition' => 'Composition', 'weight' => 'Grammage', 'fit' => 'Coupe', 'care' => 'Entretien'];

        return collect($labels)
            ->mapWithKeys(fn (string $label, string $key) => [$label => (string) ($this->specs[$key] ?? '')])
            ->filter()
            ->all();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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
            'category' => $this->category?->name,
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function imageUrl(string $conversion = 'card'): ?string
    {
        $media = $this->getFirstMedia(self::IMAGES);

        if ($media === null) {
            return null;
        }

        return $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }
}
