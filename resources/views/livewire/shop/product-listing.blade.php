@php
    $current = $this->categories->firstWhere('slug', $category);
    $theme = $current?->theme() ?? config('shop.default_category_theme');
    $hasFilters = $search !== '' || $sizes || $colors || $minPrice !== null || $maxPrice !== null;
@endphp

<div x-data="{ filters: false }">
    <header class="border-b border-line" style="background-color: {{ $theme['field'] }}; color: {{ $theme['ink'] }}">
        <div class="shell flex flex-wrap items-end justify-between gap-4 py-10 sm:py-14">
            <div>
                <h1 class="display text-6xl sm:text-8xl">{{ $current?->name ?? 'Tout' }}</h1>
                <p class="mt-3 max-w-md text-sm">{{ $current?->description ?? 'Toute la collection permanente, des basiques aux pièces de saison.' }}</p>
            </div>
            <p class="text-sm" wire:loading.class="opacity-50">
                {{ $this->products->total() }} {{ \Illuminate\Support\Str::plural('produit', $this->products->total()) }}
            </p>
        </div>
    </header>

    <div class="shell py-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" class="btn-outline h-10 lg:hidden" x-on:click="filters = !filters" x-bind:aria-expanded="filters">Filtres</button>

            <ul class="flex flex-wrap items-center gap-2 text-sm" aria-label="Filtres actifs">
                @if ($search !== '')
                    <li><button type="button" wire:click="removeFilter('search')" class="chip">« {{ $search }} » <span aria-hidden="true">×</span><span class="sr-only">Retirer la recherche</span></button></li>
                @endif
                @foreach ($sizes as $size)
                    <li><button type="button" wire:click="removeFilter('sizes', '{{ $size }}')" class="chip">{{ $size }} <span aria-hidden="true">×</span><span class="sr-only">Retirer la taille {{ $size }}</span></button></li>
                @endforeach
                @foreach ($colors as $color)
                    <li><button type="button" wire:click="removeFilter('colors', '{{ $color }}')" class="chip">{{ $color }} <span aria-hidden="true">×</span><span class="sr-only">Retirer la couleur {{ $color }}</span></button></li>
                @endforeach
                @if ($minPrice !== null)
                    <li><button type="button" wire:click="removeFilter('minPrice')" class="chip">Dès {{ $minPrice }} € <span aria-hidden="true">×</span><span class="sr-only">Retirer le prix minimum</span></button></li>
                @endif
                @if ($maxPrice !== null)
                    <li><button type="button" wire:click="removeFilter('maxPrice')" class="chip">Jusqu'à {{ $maxPrice }} € <span aria-hidden="true">×</span><span class="sr-only">Retirer le prix maximum</span></button></li>
                @endif
                @if ($hasFilters)
                    <li><button type="button" wire:click="resetFilters" class="underline underline-offset-4">Tout effacer</button></li>
                @endif
            </ul>

            <label class="ml-auto flex items-center gap-2 text-sm">
                <span class="text-muted">Trier par</span>
                <select wire:model.live="sort" class="field w-auto py-2 pr-9">
                    <option value="newest">Nouveautés</option>
                    <option value="price_asc">Prix croissant</option>
                    <option value="price_desc">Prix décroissant</option>
                </select>
            </label>
        </div>

        <div class="mt-8 grid gap-10 lg:grid-cols-[14rem_1fr]">
            <aside class="space-y-8 lg:block" x-cloak x-show="filters || window.matchMedia('(min-width: 1024px)').matches" x-transition.opacity>
                <div>
                    <label for="search" class="font-display text-lg font-bold">Recherche</label>
                    <input id="search" name="q" type="search" wire:model.live.debounce.300ms="search" placeholder="Hoodie, tee, veste…" class="field mt-3">
                </div>

                <div>
                    <p class="font-display text-lg font-bold">Catégorie</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li>
                            <button type="button" wire:click="$set('category', null)" @class(['hover:underline', 'font-semibold underline underline-offset-4' => $category === null])>Tout</button>
                        </li>
                        @foreach ($this->categories as $item)
                            <li>
                                <button type="button" wire:click="$set('category', '{{ $item->slug }}')" @class(['hover:underline', 'font-semibold underline underline-offset-4' => $category === $item->slug])>{{ $item->name }}</button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="font-display text-lg font-bold">Taille</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($this->availableSizes as $size)
                            <label class="cursor-pointer">
                                <input type="checkbox" wire:model.live="sizes" value="{{ $size }}" class="peer sr-only">
                                <span class="block min-w-11 border border-line bg-white px-3 py-2 text-center text-sm peer-checked:border-ink peer-checked:bg-ink peer-checked:text-paper peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-signal">{{ $size }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="font-display text-lg font-bold">Couleur</p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($this->availableColors as $name => $hex)
                            <label class="cursor-pointer" title="{{ $name }}">
                                <input type="checkbox" wire:model.live="colors" value="{{ $name }}" class="peer sr-only">
                                <span class="block h-7 w-7 rounded-full border border-ink/20 ring-offset-2 ring-offset-paper peer-checked:ring-2 peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-signal" style="background-color: {{ $hex }}"></span>
                                <span class="sr-only">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="font-display text-lg font-bold">Prix (€)</p>
                    <div class="mt-3 flex items-center gap-2">
                        <input type="number" min="0" inputmode="numeric" wire:model.live.debounce.500ms="minPrice" placeholder="Min" class="field" aria-label="Prix minimum">
                        <span class="text-muted">–</span>
                        <input type="number" min="0" inputmode="numeric" wire:model.live.debounce.500ms="maxPrice" placeholder="Max" class="field" aria-label="Prix maximum">
                    </div>
                </div>
            </aside>

            <section aria-live="polite">
                <div wire:loading.delay.shortest class="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 xl:grid-cols-4" aria-hidden="true">
                    @foreach (range(1, 8) as $i)
                        <div class="animate-pulse">
                            <div class="aspect-[4/5] bg-concrete"></div>
                            <div class="mt-3 h-3 w-2/3 bg-concrete"></div>
                            <div class="mt-2 h-3 w-1/4 bg-concrete"></div>
                        </div>
                    @endforeach
                </div>

                <div wire:loading.remove.delay.shortest>
                    @if ($this->products->isEmpty())
                        <div class="border border-line py-20 text-center">
                            <p class="font-display text-3xl font-bold">Rien ne correspond.</p>
                            <p class="mt-2 text-sm text-muted">Retirez un filtre ou élargissez la fourchette de prix.</p>
                            <button type="button" wire:click="resetFilters" class="btn-primary mt-6">Effacer les filtres</button>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 xl:grid-cols-4">
                            @foreach ($this->products as $product)
                                <x-shop.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                            @endforeach
                        </div>

                        <div class="mt-12">
                            {{ $this->products->links() }}
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
