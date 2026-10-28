<div
    x-data="{ open: false }"
    x-on:open-search.window="open = true; $nextTick(() => $refs.input.focus())"
    x-on:keydown.escape.window="open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
>
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-ink/40" x-on:click="open = false"></div>

    <div
        x-cloak
        x-show="open"
        x-transition:enter="transition duration-150 ease-out"
        x-transition:enter-start="-translate-y-4 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        class="fixed inset-x-0 top-0 z-50 max-h-screen overflow-y-auto bg-paper"
        role="dialog"
        aria-modal="true"
        aria-label="Recherche"
    >
        <div class="shell py-5">
            <div class="flex items-center gap-4 border-b border-ink pb-3">
                <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                <input
                    x-ref="input"
                    type="search"
                    wire:model.live.debounce.200ms="query"
                    placeholder="Rechercher un produit"
                    aria-label="Rechercher un produit"
                    autocomplete="off"
                    class="w-full border-0 bg-transparent p-0 [&::-webkit-search-cancel-button]:hidden font-display text-3xl font-bold placeholder:text-steel focus:outline-none focus:ring-0 sm:text-4xl"
                >
                <button type="button" x-on:click="open = false" class="shrink-0 text-sm underline underline-offset-4">Fermer</button>
            </div>

            <div class="py-8" wire:loading.class="opacity-60">
                @if (mb_strlen(trim($query)) < 2)
                    <p class="text-sm text-muted">Parcourir par catégorie</p>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($categories as $category)
                            <li>
                                <a href="{{ route('shop.category', $category->slug) }}" wire:navigate x-on:click="open = false" class="btn-outline h-10">{{ $category->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @elseif ($results->isEmpty())
                    <p class="text-lg font-medium">Aucun résultat pour « {{ $query }} ».</p>
                    <p class="mt-1 text-sm text-muted">Vérifiez l'orthographe ou essayez un mot plus court, comme « hoodie » ou « cargo ».</p>
                @else
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach ($results as $product)
                            <li wire:key="result-{{ $product->id }}">
                                <a href="{{ route('shop.show', $product) }}" wire:navigate x-on:click="open = false" class="group block">
                                    <x-shop.thumb :product="$product" class="flex aspect-[4/5]" />
                                    <p class="mt-2 text-sm font-medium group-hover:underline">{{ $product->name }}</p>
                                    <p class="text-sm text-muted">{{ \App\Support\Money::format($product->price()) }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('shop.index', ['search' => $query]) }}" wire:navigate x-on:click="open = false" class="mt-8 inline-block text-sm font-medium underline underline-offset-4">Voir tous les résultats</a>
                @endif
            </div>
        </div>
    </div>
</div>
