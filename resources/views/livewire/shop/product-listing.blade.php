<div class="shell py-10" x-data="{ filters: false }">
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
        <div>
            <p class="eyebrow">Boutique</p>
            <h1 class="mt-2 text-3xl sm:text-4xl">
                {{ $this->categories->firstWhere('slug', $category)?->name ?? 'Tous les produits' }}
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" class="btn-outline lg:hidden" x-on:click="filters = !filters">Filtres</button>
            <select wire:model.live="sort" class="field w-auto py-2.5 pr-10" aria-label="Trier par">
                <option value="newest">Nouveautés</option>
                <option value="price_asc">Prix croissant</option>
                <option value="price_desc">Prix décroissant</option>
            </select>
        </div>
    </div>

    <div class="mt-8 grid gap-10 lg:grid-cols-[16rem_1fr]">
        <aside class="space-y-8 lg:block" x-bind:class="filters ? 'block' : 'hidden'">
            <div>
                <label for="search" class="eyebrow">Recherche</label>
                <input id="search" type="search" wire:model.live.debounce.300ms="search" placeholder="Hoodie, tee, veste…" class="field mt-3">
            </div>

            <div>
                <p class="eyebrow">Catégorie</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li>
                        <button type="button" wire:click="$set('category', null)" @class(['hover:text-muted', 'font-semibold underline underline-offset-4' => $category === null])>Tout</button>
                    </li>
                    @foreach ($this->categories as $item)
                        <li>
                            <button type="button" wire:click="$set('category', '{{ $item->slug }}')" @class(['hover:text-muted', 'font-semibold underline underline-offset-4' => $category === $item->slug])>{{ $item->name }}</button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p class="eyebrow">Taille</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($this->availableSizes as $size)
                        <label class="cursor-pointer">
                            <input type="checkbox" wire:model.live="sizes" value="{{ $size }}" class="peer sr-only">
                            <span class="block min-w-11 border border-line bg-white px-3 py-2 text-center text-xs peer-checked:border-ink peer-checked:bg-ink peer-checked:text-paper">{{ $size }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="eyebrow">Couleur</p>
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach ($this->availableColors as $name => $hex)
                        <label class="cursor-pointer" title="{{ $name }}">
                            <input type="checkbox" wire:model.live="colors" value="{{ $name }}" class="peer sr-only">
                            <span class="block h-7 w-7 rounded-full border border-line ring-offset-2 ring-offset-paper peer-checked:ring-2 peer-checked:ring-ink" style="background-color: {{ $hex }}"></span>
                            <span class="sr-only">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="eyebrow">Prix (€)</p>
                <div class="mt-3 flex items-center gap-2">
                    <input type="number" min="0" wire:model.live.debounce.500ms="minPrice" placeholder="Min" class="field" aria-label="Prix minimum">
                    <span class="text-muted">–</span>
                    <input type="number" min="0" wire:model.live.debounce.500ms="maxPrice" placeholder="Max" class="field" aria-label="Prix maximum">
                </div>
            </div>

            <button type="button" wire:click="resetFilters" class="text-xs uppercase tracking-[0.14em] underline underline-offset-4 hover:text-muted">Réinitialiser</button>
        </aside>

        <section>
            <p class="mb-6 text-sm text-muted" wire:loading.class="opacity-50">
                {{ $this->products->total() }} {{ \Illuminate\Support\Str::plural('produit', $this->products->total()) }}
            </p>

            @if ($this->products->isEmpty())
                <div class="border border-line py-20 text-center">
                    <p class="text-lg font-semibold">Aucun produit ne correspond à votre recherche.</p>
                    <button type="button" wire:click="resetFilters" class="btn-outline mt-6">Réinitialiser les filtres</button>
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-5 gap-y-10 md:grid-cols-3" wire:loading.class="opacity-50">
                    @foreach ($this->products as $product)
                        <x-shop.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>

                <div class="mt-12">
                    {{ $this->products->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
