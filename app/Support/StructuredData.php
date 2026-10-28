<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/** schema.org JSON-LD documents for search engines. */
final class StructuredData
{
    /** @return array<string, mixed> */
    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Config::string('app.name'),
            'legalName' => Config::string('shop.company.name'),
            'url' => url('/'),
            'logo' => asset('favicon.svg'),
            'email' => Config::string('shop.company.email'),
        ];
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @return array<string, mixed>
     */
    public static function product(Product $product, Collection $variants, int $reviewCount, float $averageRating): array
    {
        $prices = $variants->map(fn (ProductVariant $variant): int => $variant->price());
        $inStock = $product->is_gift_card || $variants->contains(fn (ProductVariant $variant): bool => $variant->canBeOrdered());
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->description,
            'sku' => $variants->first()?->sku,
            'brand' => ['@type' => 'Brand', 'name' => Config::string('app.name')],
            'image' => $product->imageUrl('card') ? [$product->imageUrl('card')] : null,
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => Config::string('shop.currency'),
                'lowPrice' => self::amount($prices->min() ?? $product->price()),
                'highPrice' => self::amount($prices->max() ?? $product->price()),
                'offerCount' => max(1, $variants->count()),
                'availability' => 'https://schema.org/'.($inStock ? 'InStock' : 'OutOfStock'),
                'url' => route('shop.show', $product),
            ],
        ];

        if ($reviewCount > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($averageRating, 1, '.', ''),
                'reviewCount' => $reviewCount,
            ];
        }

        return array_filter($data, fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  list<array{name: string, url: string}>  $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $crumb, int $index): array => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $crumb['name'], 'item' => $crumb['url']],
                $crumbs,
                array_keys($crumbs),
            ),
        ];
    }

    /**
     * Safe to embed in a `<script>` tag.
     *
     * @param  array<string, mixed>  $data
     */
    public static function toJson(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    private static function amount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
