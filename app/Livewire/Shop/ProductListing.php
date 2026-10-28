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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read Collection<int, Category> $categories
 */
#[Layout('components.layouts.shop')]
final class ProductListing extends Component
{
    use WithPagination;

    private const array SIZE_ORDER = ['XS', 'S', 'M', 'L', 'XL', 'Unique'];

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

    public function mount(?string $categorySlug = null): void
    {
        if ($categorySlug !== null) {
            abort_unless(Category::query()->where('slug', $categorySlug)->exists(), 404);
            $this->category = $categorySlug;
        }
    }

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
        return $this->filterableVariants()->distinct()->get(['size'])
            ->map(fn (ProductVariant $variant): string => $variant->size)
            ->sortBy(fn (string $size): false|int => array_search($size, self::SIZE_ORDER, true))
            ->values()
            ->toBase();
    }

    /** @return Collection<string, string|null> color name => hex */
    #[Computed]
    public function availableColors(): Collection
    {
        return $this->filterableVariants()->select('color', 'color_hex')->distinct()->orderBy('color')->get()
            ->mapWithKeys(fn (ProductVariant $variant): array => [$variant->color => $variant->color_hex])
            ->toBase();
    }

    /**
     * Gift card variants carry an amount in `size`, so they stay out of the clothing filters.
     *
     * @return Builder<ProductVariant>
     */
    private function filterableVariants(): Builder
    {
        return ProductVariant::query()->whereHas('product', fn (Builder $product) => $product->where('is_active', true)->where('is_gift_card', false));
    }

    public function render(): View
    {
        /** @var Category|null $current */
        $current = $this->categories->firstWhere('slug', $this->category);
        $filtered = trim($this->search) !== '' || $this->sizes || $this->colors || $this->minPrice !== null || $this->maxPrice !== null || $this->sort !== 'newest';

        $default = 'Toute la collection permanente 4shion : hoodies, t-shirts, vestes, pantalons et accessoires.';

        return view('livewire.shop.product-listing')
            ->title($current instanceof Category ? ($current->meta_title ?? $current->name) : 'Boutique')
            ->layoutData([
                'description' => $current instanceof Category ? ($current->meta_description ?? $current->description ?? $default) : $default,
                'canonical' => $current instanceof Category ? route('shop.category', $current) : route('shop.index'),
                // Filtered and sorted views duplicate the category page: keep them out of the index.
                'noindex' => $filtered,
            ]);
    }
}
