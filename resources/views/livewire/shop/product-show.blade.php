@php
    $images = $product->getMedia(\App\Models\Product::IMAGES);
@endphp

<div class="shell py-10" x-data="{ active: 0 }">
    <nav class="mb-8 text-xs text-muted" aria-label="Fil d'Ariane">
        <a href="{{ route('home') }}" wire:navigate class="hover:text-ink">Accueil</a> /
        <a href="{{ route('shop.index', ['category' => $product->category->slug]) }}" wire:navigate class="hover:text-ink">{{ $product->category->name }}</a> /
        <span class="text-ink">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">
        <div class="space-y-3">
            @forelse ($images as $index => $image)
                <div class="aspect-[4/5] overflow-hidden bg-[#EBE7DE]" x-show="active === {{ $index }}" @unless ($loop->first) x-cloak @endunless>
                    <img src="{{ $image->hasGeneratedConversion('detail') ? $image->getUrl('detail') : $image->getUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                </div>
            @empty
                <div class="flex aspect-[4/5] items-center justify-center bg-[#EBE7DE] text-7xl font-semibold uppercase tracking-brand text-sand">4</div>
            @endforelse

            @if ($images->count() > 1)
                <div class="flex gap-3">
                    @foreach ($images as $index => $image)
                        <button type="button" x-on:click="active = {{ $index }}" class="h-20 w-16 overflow-hidden border border-line" x-bind:class="active === {{ $index }} && 'border-ink'">
                            <img src="{{ $image->hasGeneratedConversion('card') ? $image->getUrl('card') : $image->getUrl() }}" alt="" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lg:pt-6">
            <p class="eyebrow">{{ $product->category->name }}</p>
            <h1 class="mt-3 text-3xl sm:text-4xl">{{ $product->name }}</h1>

            @if ($this->reviews->isNotEmpty())
                <a href="#avis" class="mt-3 inline-flex items-center gap-2 text-sm text-muted">
                    <span class="text-ink">{{ str_repeat('★', (int) round($this->averageRating)) }}{{ str_repeat('☆', 5 - (int) round($this->averageRating)) }}</span>
                    {{ number_format($this->averageRating, 1, ',') }} ({{ $this->reviews->count() }} avis)
                </a>
            @endif

            <p class="mt-5 text-2xl">{{ \App\Support\Money::format($this->price) }}</p>
            <p class="mt-6 max-w-prose text-sm leading-relaxed text-muted">{{ $product->description }}</p>

            <div class="mt-8">
                <p class="eyebrow">Couleur : <span class="text-ink">{{ $color }}</span></p>
                <div class="mt-3 flex gap-3">
                    @foreach ($this->colors as $name => $hex)
                        <button type="button" wire:click="selectColor('{{ $name }}')" title="{{ $name }}"
                            @class(['h-9 w-9 rounded-full border border-line ring-offset-2 ring-offset-paper', 'ring-2 ring-ink' => $color === $name])
                            style="background-color: {{ $hex }}">
                            <span class="sr-only">{{ $name }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-8">
                <p class="eyebrow">Taille</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($this->sizesForColor as $sizeName => $variant)
                        <button type="button" wire:click="selectSize('{{ $sizeName }}')" @disabled(! $variant->isInStock())
                            @class([
                                'min-w-14 border px-4 py-3 text-sm',
                                'border-ink bg-ink text-paper' => $size === $sizeName,
                                'border-line bg-white hover:border-ink' => $size !== $sizeName && $variant->isInStock(),
                                'cursor-not-allowed border-line bg-transparent text-muted line-through' => ! $variant->isInStock(),
                            ])>
                            {{ $sizeName }}
                        </button>
                    @endforeach
                </div>
                @if ($this->selectedVariant && $this->selectedVariant->stock <= config('shop.low_stock_threshold'))
                    <p class="mt-3 text-xs text-muted">Plus que {{ $this->selectedVariant->stock }} en stock.</p>
                @endif
            </div>
        </div>
    </div>

    <section id="avis" class="mt-24 border-t border-line pt-12">
        <h2 class="text-2xl">Avis clients</h2>

        @forelse ($this->reviews as $review)
            <article class="mt-8 max-w-2xl border-b border-line pb-8" wire:key="review-{{ $review->id }}">
                <p class="text-sm">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</p>
                @if ($review->title)
                    <h3 class="mt-2 text-base font-semibold">{{ $review->title }}</h3>
                @endif
                <p class="mt-2 text-sm text-muted">{{ $review->body }}</p>
                <p class="mt-3 text-xs text-muted">{{ $review->user->name }} · {{ $review->created_at->translatedFormat('j F Y') }}</p>
            </article>
        @empty
            <p class="mt-6 text-sm text-muted">Aucun avis pour le moment.</p>
        @endforelse
    </section>
</div>
