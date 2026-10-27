<?php

namespace App\Actions\Catalog;

use App\Data\ProductFilters;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

class ListProducts
{
    private const SEARCH_LIMIT = 200;

    /**
     * Active products matching the filters. Full-text matching goes through Scout,
     * every other filter and the sorting are applied in SQL.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function __invoke(ProductFilters $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->active()
            ->with(['category', 'media', 'variants']);

        if ($filters->search !== '') {
            $ids = Product::search($filters->search)->take(self::SEARCH_LIMIT)->keys();
            $query->whereIn('id', $ids);
        }

        $query
            ->when($filters->category, fn (Builder $q, string $slug) => $q->whereHas('category', fn (Builder $c) => $c->where('slug', $slug)))
            ->when($filters->sizes || $filters->colors, fn (Builder $q) => $q->whereHas('variants', function (Builder $v) use ($filters): void {
                $v->when($filters->sizes, fn (Builder $x) => $x->whereIn('size', $filters->sizes))
                    ->when($filters->colors, fn (Builder $x) => $x->whereIn('color', $filters->colors));
            }))
            ->when($filters->minPrice !== null, fn (Builder $q) => $q->where('base_price', '>=', $filters->minPrice))
            ->when($filters->maxPrice !== null, fn (Builder $q) => $q->where('base_price', '<=', $filters->maxPrice));

        match ($filters->sort) {
            'price_asc' => $query->orderBy('base_price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('base_price')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query->paginate(Config::integer('shop.per_page'))->withQueryString();
    }
}
