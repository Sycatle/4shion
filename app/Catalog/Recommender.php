<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Enums\OrderStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class Recommender
{
    private const array COUNTED_STATUSES = [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered];

    /**
     * Products most often found in the same paid orders, topped up with the same category.
     *
     * @return Collection<int, Product>
     */
    public function boughtTogether(Product $product, int $limit = 4): Collection
    {
        $statuses = array_map(fn (OrderStatus $status): string => $status->value, self::COUNTED_STATUSES);

        $orderIds = DB::table('order_items')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('product_variants.product_id', $product->id)
            ->whereIn('orders.status', $statuses)
            ->select('order_items.order_id');

        $ids = array_values(array_map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, DB::table('order_items')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->whereIn('order_items.order_id', $orderIds)
            ->where('product_variants.product_id', '!=', $product->id)
            ->groupBy('product_variants.product_id')
            ->orderByRaw('count(*) desc')
            ->limit($limit)
            ->pluck('product_variants.product_id')
            ->all()));

        $together = $this->hydrate($ids);

        if ($together->count() >= $limit) {
            return $together;
        }

        $exclude = [...$ids, $product->id];

        return $together->concat($this->similar($product, $limit - $together->count(), $exclude))->values();
    }

    /**
     * @param  list<int>  $exclude
     * @return Collection<int, Product>
     */
    public function similar(Product $product, int $limit = 4, array $exclude = []): Collection
    {
        return $this->listable()
            ->where('category_id', $product->category_id)
            ->whereNotIn('id', [...$exclude, $product->id])
            ->orderByDesc('is_featured')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Cross-sell for a cart: other products from the categories already in it.
     *
     * @param  list<int>  $productIds  products already in the cart
     * @return Collection<int, Product>
     */
    public function forCart(array $productIds, int $limit = 4): Collection
    {
        if ($productIds === []) {
            return new Collection;
        }

        return $this->listable()
            ->whereIn('category_id', Product::query()->whereIn('id', $productIds)->select('category_id'))
            ->whereNotIn('id', $productIds)
            ->where('is_gift_card', false)
            ->orderByDesc('is_featured')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    public function hydrate(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return $this->listable()->whereIn('id', $ids)->get()
            ->sortBy(fn (Product $product): int|false => array_search($product->id, $ids, true))
            ->values();
    }

    /** @return Builder<Product> */
    private function listable(): Builder
    {
        return Product::query()->active()->withRating()->with(['category', 'media', 'variants']);
    }
}
