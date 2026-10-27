<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

final class SearchOverlay extends Component
{
    private const int MIN_LENGTH = 2;

    private const int LIMIT = 6;

    public string $query = '';

    /**
     * @return Collection<int, Product>
     */
    private function results(): Collection
    {
        $term = trim($this->query);

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return new Collection;
        }

        $ranking = Product::search($term)->take(50)->keys()->flip();

        return Product::query()
            ->active()
            ->with(['media', 'category'])
            ->whereIn('id', $ranking->keys())
            ->get()
            ->sortBy(fn (Product $product): int => (int) $ranking->get($product->id, PHP_INT_MAX))
            ->take(self::LIMIT)
            ->values();
    }

    public function render(): View
    {
        return view('livewire.shop.search-overlay', [
            'results' => $this->results(),
            'categories' => Category::query()->orderBy('position')->get(),
        ]);
    }
}
