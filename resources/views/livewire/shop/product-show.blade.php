@php
    $images = $product->getMedia(\App\Models\Product::IMAGES);
    $theme = $product->category->theme();
    $specs = $product->specSheet();
@endphp

<div class="shell py-8" x-data="{ active: 0 }">
    <nav class="mb-6 text-sm text-muted" aria-label="Fil d'Ariane">
        <a href="{{ route('home') }}" wire:navigate class="hover:text-ink hover:underline">Accueil</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('shop.index', ['category' => $product->category->slug]) }}" wire:navigate class="hover:text-ink hover:underline">{{ $product->category->name }}</a>
        <span aria-hidden="true">/</span>
        <span class="text-ink" aria-current="page">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-10 lg:grid-cols-[7fr_5fr] lg:gap-16">
        <div class="space-y-3">
            @forelse ($images as $index => $image)
                <div class="aspect-[4/5] overflow-hidden" style="background-color: {{ $theme['field'] }}" x-show="active === {{ $index }}" @unless ($loop->first) x-cloak @endunless>
                    <img src="{{ $image->hasGeneratedConversion('detail') ? $image->getUrl('detail') : $image->getUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover mix-blend-multiply" @if ($loop->first) fetchpriority="high" @endif>
                </div>
            @empty
                <x-shop.thumb :product="$product" class="flex aspect-[4/5]" />
            @endforelse

            @if ($images->count() > 1)
                <div class="flex gap-3">
                    @foreach ($images as $index => $image)
                        <button type="button" x-on:click="active = {{ $index }}" class="h-20 w-16 overflow-hidden border border-line" style="background-color: {{ $theme['field'] }}" x-bind:class="active === {{ $index }} && 'border-ink'" aria-label="Voir l'image {{ $index + 1 }}">
                            <img src="{{ $image->hasGeneratedConversion('card') ? $image->getUrl('card') : $image->getUrl() }}" alt="" class="h-full w-full object-cover mix-blend-multiply">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h1 class="display text-6xl sm:text-7xl">{{ $product->name }}</h1>

            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2">
                <p class="font-display text-3xl font-bold">{{ \App\Support\Money::format($this->price) }}</p>
                @if ($this->reviews->isNotEmpty())
                    <a href="#avis" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink">
                        <x-shop.stars :rating="$this->averageRating" />
                        {{ number_format($this->averageRating, 1, ',') }} ({{ $this->reviews->count() }} {{ \Illuminate\Support\Str::plural('avis', $this->reviews->count()) }})
                    </a>
                @endif
            </div>

            <p class="mt-6 max-w-prose text-base leading-relaxed">{{ $product->description }}</p>

            <div class="mt-8">
                <p class="text-sm font-semibold">Couleur : <span class="font-normal">{{ $color }}</span></p>
                <div class="mt-3 flex gap-3">
                    @foreach ($this->colors as $name => $hex)
                        <button type="button" wire:click="selectColor('{{ $name }}')" title="{{ $name }}" aria-pressed="{{ $color === $name ? 'true' : 'false' }}"
                            @class(['h-9 w-9 rounded-full border border-ink/20 ring-offset-2 ring-offset-paper', 'ring-2 ring-ink' => $color === $name])
                            style="background-color: {{ $hex }}">
                            <span class="sr-only">{{ $name }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-8">
                <div class="flex items-baseline justify-between">
                    <p class="text-sm font-semibold">Taille</p>
                    @if ($product->category->slug !== 'accessoires')
                        <button type="button" x-data x-on:click="$dispatch('open-size-guide')" class="text-sm underline underline-offset-4">Guide des tailles</button>
                    @endif
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($this->sizesForColor as $sizeName => $variant)
                        <button type="button" wire:click="selectSize('{{ $sizeName }}')" @disabled(! $variant->isInStock()) aria-pressed="{{ $size === $sizeName ? 'true' : 'false' }}"
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
                    <p class="mt-3 text-sm text-muted">Plus que {{ $this->selectedVariant->stock }} en stock.</p>
                @endif
            </div>

            <div class="mt-8 space-y-3">
                <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-primary hidden w-full lg:inline-flex">Ajouter au panier</button>
                @if ($error)
                    <p class="text-sm text-red-700" role="alert">{{ $error }}</p>
                @endif
                <p class="text-sm text-muted">Livraison en {{ config('shop.shipping.delay') }}. Retours gratuits sous 30 jours.</p>
            </div>

            @if ($specs)
                <dl class="mt-10 divide-y divide-line border-y border-line text-sm">
                    @foreach ($specs as $label => $value)
                        <div class="grid grid-cols-[8rem_1fr] gap-4 py-3">
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-30 flex items-center gap-4 border-t border-line bg-paper px-5 py-3 lg:hidden">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold">{{ $product->name }}</p>
            <p class="text-sm text-muted">{{ \App\Support\Money::format($this->price) }}@if ($size) · {{ $size }}@endif</p>
        </div>
        <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-primary ml-auto shrink-0">Ajouter</button>
    </div>

    @if ($product->category->slug !== 'accessoires')
        <div x-data="{ open: false }" x-on:open-size-guide.window="open = true" x-on:keydown.escape.window="open = false">
            <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-5" x-on:click.self="open = false">
                <div class="w-full max-w-lg bg-paper p-6" role="dialog" aria-modal="true" aria-labelledby="size-guide-title">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 id="size-guide-title" class="text-3xl">Guide des tailles</h2>
                            <p class="mt-1 text-sm text-muted">Mesures du vêtement à plat, en centimètres.</p>
                        </div>
                        <button type="button" x-on:click="open = false" aria-label="Fermer" class="p-1">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                    <table class="mt-6 w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-ink">
                                @foreach (config('shop.size_guide.columns') as $column)
                                    <th scope="col" class="py-2 font-semibold">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach (config('shop.size_guide.rows') as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td class="py-2 {{ $loop->first ? 'font-semibold' : '' }}">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <section id="avis" class="mt-24 border-t border-line pt-12 pb-24 lg:pb-0">
        <div class="flex flex-wrap items-baseline gap-4">
            <h2 class="text-4xl">Avis clients</h2>
            @if ($this->reviews->isNotEmpty())
                <p class="flex items-center gap-2 text-sm text-muted"><x-shop.stars :rating="$this->averageRating" /> {{ number_format($this->averageRating, 1, ',') }} sur 5, {{ $this->reviews->count() }} {{ \Illuminate\Support\Str::plural('avis', $this->reviews->count()) }}</p>
            @endif
        </div>

        @if ($reviewSubmitted)
            <p class="mt-6 max-w-2xl border border-ink bg-white px-4 py-3 text-sm" role="status">Merci ! Votre avis sera publié après modération.</p>
        @elseif ($this->canReview)
            <form wire:submit="submitReview" class="mt-6 max-w-2xl space-y-4 border border-line bg-white p-6">
                <p class="font-display text-xl font-bold">Donner mon avis</p>
                <div>
                    <label for="rating" class="mb-2 block text-xs text-muted">Note</label>
                    <select id="rating" wire:model="rating" class="field w-auto pr-10">
                        @foreach ([5, 4, 3, 2, 1] as $value)
                            <option value="{{ $value }}">{{ $value }} sur 5</option>
                        @endforeach
                    </select>
                    @error('rating') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reviewTitle" class="mb-2 block text-xs text-muted">Titre (facultatif)</label>
                    <input id="reviewTitle" type="text" wire:model="reviewTitle" class="field">
                    @error('reviewTitle') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reviewBody" class="mb-2 block text-xs text-muted">Votre avis</label>
                    <textarea id="reviewBody" rows="4" wire:model="reviewBody" class="field"></textarea>
                    @error('reviewBody') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary">Publier mon avis</button>
            </form>
        @endif

        @forelse ($this->reviews as $review)
            <article class="mt-8 max-w-2xl border-b border-line pb-8" wire:key="review-{{ $review->id }}">
                <x-shop.stars :rating="$review->rating" />
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
