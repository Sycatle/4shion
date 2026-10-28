<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;

#[Fillable(['name', 'slug', 'description', 'position', 'content', 'meta_title', 'meta_description'])]
#[RouteKey('slug')]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, LogsActivity;

    /** @return array{field: string, ink: string} */
    public function theme(): array
    {
        $theme = Config::has("shop.category_themes.{$this->slug}")
            ? "shop.category_themes.{$this->slug}"
            : 'shop.default_category_theme';

        return [
            'field' => Config::string("{$theme}.field"),
            'ink' => Config::string("{$theme}.ink"),
        ];
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
