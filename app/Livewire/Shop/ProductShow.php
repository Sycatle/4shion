<?php

namespace App\Livewire\Shop;

use App\Cart\CartManager;
use App\Exceptions\Cart\CartException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection<int, ProductVariant> $variants
 * @property-read Collection<string, string|null> $colors
 * @property-read Collection<string, ProductVariant> $sizesForColor
 * @property-read ProductVariant|null $selectedVariant
 * @property-read int $price
 * @property-read Collection<int, Review> $reviews
 * @property-read float $averageRating
 * @property-read bool $canReview
 */
#[Layout('components.layouts.shop')]
class ProductShow extends Component
{
    public Product $product;

    public ?string $color = null;

    public ?string $size = null;

    public ?string $error = null;

    public bool $added = false;

    public int $rating = 5;

    public ?string $reviewTitle = null;

    public string $reviewBody = '';

    public bool $reviewSubmitted = false;

    public function mount(Product $product): void
    {
        abort_unless($product->is_active, 404);

        $this->product = $product->load(['category', 'media']);
        // Preselect the first color that still has stock.
        $this->color = $this->variants->sortByDesc(fn (ProductVariant $v) => $v->isInStock())->pluck('color')->first();
    }

    public function selectColor(string $color): void
    {
        $this->color = $color;

        if ($this->size !== null && ! $this->sizesForColor->get($this->size)?->isInStock()) {
            $this->size = null;
        }
    }

    public function selectSize(string $size): void
    {
        if ($this->sizesForColor->get($size)?->isInStock()) {
            $this->size = $size;
        }
    }

    public function addToCart(CartManager $cart): void
    {
        $this->error = null;
        $this->added = false;

        if ($this->selectedVariant === null) {
            $this->error = 'Veuillez choisir une taille.';

            return;
        }

        try {
            $cart->add($this->selectedVariant);
        } catch (CartException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->added = true;
        $this->dispatch('cart-updated');
        $this->dispatch('cart-added');
    }

    public function submitReview(): void
    {
        abort_unless(auth()->check(), 403);
        Gate::authorize('review', $this->product);

        $data = $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'reviewTitle' => ['nullable', 'string', 'max:120'],
            'reviewBody' => ['required', 'string', 'min:10', 'max:2000'],
        ], attributes: [
            'rating' => 'note',
            'reviewTitle' => 'titre',
            'reviewBody' => 'avis',
        ]);

        $this->product->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $data['rating'],
            'title' => $data['reviewTitle'],
            'body' => $data['reviewBody'],
        ]);

        $this->reset('reviewTitle', 'reviewBody', 'rating');
        $this->reviewSubmitted = true;
    }

    #[Computed]
    public function canReview(): bool
    {
        return auth()->check() && Gate::allows('review', $this->product);
    }

    /** @return Collection<int, ProductVariant> */
    #[Computed]
    public function variants(): Collection
    {
        return $this->product->variants()->orderBy('id')->get();
    }

    /** @return Collection<string, string|null> color name => hex */
    #[Computed]
    public function colors(): Collection
    {
        return $this->variants->unique('color')->pluck('color_hex', 'color');
    }

    /** @return Collection<string, ProductVariant> variants of the selected color keyed by size */
    #[Computed]
    public function sizesForColor(): Collection
    {
        return $this->variants->where('color', $this->color)->keyBy('size');
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        return $this->size !== null ? $this->sizesForColor->get($this->size) : null;
    }

    /** Unit price in cents for the current selection (cheapest variant price otherwise). */
    #[Computed]
    public function price(): int
    {
        return $this->selectedVariant?->price() ?? $this->product->base_price;
    }

    /** @return Collection<int, Review> */
    #[Computed]
    public function reviews(): Collection
    {
        return $this->product->reviews()->approved()->with('user:id,name')->latest()->get();
    }

    #[Computed]
    public function averageRating(): float
    {
        return round((float) $this->reviews->avg('rating'), 1);
    }

    public function render(): View
    {
        return view('livewire.shop.product-show')->title($this->product->name);
    }
}
