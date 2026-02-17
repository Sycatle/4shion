<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'position'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return array{field: string, ink: string} */
    public function theme(): array
    {
        return config("shop.category_themes.{$this->slug}", config('shop.default_category_theme'));
    }

    public function imageUrl(): ?string
    {
        $path = "images/categories/{$this->slug}.webp";

        return file_exists(public_path($path)) ? asset($path) : null;
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
