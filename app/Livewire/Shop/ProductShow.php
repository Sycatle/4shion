<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Cart\CartManager;
use App\Catalog\RecentlyViewed;
use App\Catalog\Recommender;
use App\Exceptions\Cart\CartException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection<int, ProductVariant> $variants
 * @property-read Collection<string, string|null> $colors
 * @property-read Collection<string, ProductVariant> $sizesForColor
 * @property-read ProductVariant|null $selectedVariant
 * @property-read int $price
 * @property-read int $regularPrice
 * @property-read Collection<int, Review> $reviews
 * @property-read float $averageRating
 * @property-read bool $canReview
 * @property-read bool $inWishlist
 * @property-read list<int> $votedReviewIds
 * @property-read Collection<int, Product> $boughtTogether
 * @property-read Collection<int, Product> $recentlyViewed
 */
#[Layout('components.layouts.shop')]
final class ProductShow extends Component
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

    public string $alertEmail = '';

    public bool $alertSubscribed = false;

    public function mount(Product $product): void
    {
        abort_unless($product->is_active, 404);

        $this->product = $product->load(['category', 'media']);
        // Preselect the first color that still has stock.
        $this->color = $this->variants->sortByDesc(fn (ProductVariant $v): bool => $v->canBeOrdered())->first()?->color;
        $this->alertEmail = auth()->user()->email ?? '';

        app(RecentlyViewed::class)->record($product);
    }

    public function toggleWishlist(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $user->wishlist()->toggle($this->product->id);
        unset($this->inWishlist);
    }

    public function voteHelpful(int $reviewId): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $review = $this->product->reviews()->approved()->findOrFail($reviewId);

        if ($review->user_id === $user->id) {
            return;
        }

        $vote = ReviewVote::query()->firstOrCreate(['review_id' => $review->id, 'user_id' => $user->id]);

        if (! $vote->wasRecentlyCreated) {
            $vote->delete();
        }

        $review->update(['helpful_count' => $review->votes()->count()]);
        unset($this->reviews, $this->votedReviewIds);
    }

    #[Computed]
    public function inWishlist(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->wishlist()->whereKey($this->product->id)->exists();
    }

    /** @return list<int> */
    #[Computed]
    public function votedReviewIds(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $votes = ReviewVote::query()->where('user_id', $user->id)->whereIn('review_id', $this->reviews->pluck('id'))->pluck('review_id');

        return array_values(array_map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $votes->all()));
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function boughtTogether(): Collection
    {
        return app(Recommender::class)->boughtTogether($this->product);
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function recentlyViewed(): Collection
    {
        return app(RecentlyViewed::class)->products(except: $this->product);
    }

    public function selectColor(string $color): void
    {
        $this->color = $color;

        $this->alertSubscribed = false;

        if ($this->size !== null && ! $this->sizesForColor->has($this->size)) {
            $this->size = null;
        }
    }

    /** Sold-out sizes stay selectable so the customer can ask to be told when they are back. */
    public function selectSize(string $size): void
    {
        if ($this->sizesForColor->has($size)) {
            $this->size = $size;
            $this->error = null;
            $this->alertSubscribed = false;
        }
    }

    public function subscribeToAlert(): void
    {
        $variant = $this->selectedVariant;

        abort_if($variant === null || $variant->canBeOrdered(), 422);

        $key = 'stock-alert:'.(auth()->id() ?? request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('alertEmail', sprintf('Trop de tentatives. Réessayez dans %d secondes.', RateLimiter::availableIn($key)));

            return;
        }

        RateLimiter::hit($key);

        $this->validate(['alertEmail' => ['required', 'email:rfc', 'max:255']], attributes: ['alertEmail' => 'e-mail']);

        $user = auth()->user();

        StockAlert::query()->updateOrCreate(
            ['product_variant_id' => $variant->id, 'email' => mb_strtolower($this->alertEmail)],
            ['user_id' => $user instanceof User ? $user->id : null, 'notified_at' => null],
        );

        $this->alertSubscribed = true;
    }

    public function addToCart(CartManager $cart): void
    {
        $this->error = null;
        $this->added = false;

        if ($this->selectedVariant === null) {
            $this->error = 'Veuillez choisir une taille.';

            return;
        }

        if (! $this->selectedVariant->canBeOrdered()) {
            $this->error = 'Cet article est actuellement épuisé.';

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

        $this->validate([
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
            'rating' => $this->rating,
            'title' => $this->reviewTitle,
            'body' => $this->reviewBody,
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
        return $this->product->variants()->orderBy('id')->get()
            ->each(fn (ProductVariant $variant): ProductVariant => $variant->setRelation('product', $this->product));
    }

    /** @return Collection<string, string|null> color name => hex */
    #[Computed]
    public function colors(): Collection
    {
        return $this->variants->unique('color')->mapWithKeys(fn (ProductVariant $v): array => [$v->color => $v->color_hex]);
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

    /** Unit price in cents for the current selection, sale included. */
    #[Computed]
    public function price(): int
    {
        return $this->selectedVariant?->price() ?? $this->product->price();
    }

    /** Price before the sale, to strike through when it differs from the price. */
    #[Computed]
    public function regularPrice(): int
    {
        return $this->selectedVariant?->regularPrice() ?? $this->product->base_price;
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
        return view('livewire.shop.product-show')
            ->title($this->product->name)
            ->layoutData([
                'description' => Str::limit(strip_tags((string) ($this->product->description ?? "{$this->product->name}, {$this->product->category->name} {$this->product->price()}")), 155),
                'canonical' => route('shop.show', $this->product),
                'image' => $this->product->imageUrl('card'),
                'ogType' => 'product',
            ]);
    }
}
