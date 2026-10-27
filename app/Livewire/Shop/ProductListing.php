<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Actions\Catalog\ListProducts;
use App\Data\ProductFilters;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shop')]
#[Title('Boutique')]
final class ProductListing extends Component
{
    use WithPagination;

    private const SIZE_ORDER = ['XS', 'S', 'M', 'L', 'XL', 'Unique'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public ?string $category = null;

    /** @var list<string> */
    #[Url]
    public array $sizes = [];

    /** @var list<string> */
    #[Url]
    public array $colors = [];

    /** Prices are entered in euros and converted to cents for the query. */
    #[Url]
    public ?int $minPrice = null;

    #[Url]
    public ?int $maxPrice = null;

    #[Url]
    public string $sort = 'newest';

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function removeFilter(string $property, ?string $value = null): void
    {
        if (in_array($property, ['sizes', 'colors'], true)) {
            $this->{$property} = array_values(array_diff($this->{$property}, [$value]));
        } else {
            $this->reset($property);
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'category', 'sizes', 'colors', 'minPrice', 'maxPrice', 'sort');
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, Product> */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return app(ListProducts::class)(new ProductFilters(
            search: trim($this->search),
            category: $this->category,
            sizes: $this->sizes,
            colors: $this->colors,
            minPrice: $this->minPrice !== null ? $this->minPrice * 100 : null,
            maxPrice: $this->maxPrice !== null ? $this->maxPrice * 100 : null,
            sort: $this->sort,
        ));
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('position')->get();
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function availableSizes(): Collection
    {
        return ProductVariant::query()->distinct()->get(['size'])
            ->map(fn (ProductVariant $variant): string => $variant->size)
            ->sortBy(fn (string $size) => array_search($size, self::SIZE_ORDER, true))
            ->values()
            ->toBase();
    }

    /** @return Collection<string, string|null> color name => hex */
    #[Computed]
    public function availableColors(): Collection
    {
        return ProductVariant::query()->select('color', 'color_hex')->distinct()->orderBy('color')->get()
            ->mapWithKeys(fn (ProductVariant $variant) => [$variant->color => $variant->color_hex])
            ->toBase();
    }

    public function render(): View
    {
        return view('livewire.shop.product-listing');
    }
}
