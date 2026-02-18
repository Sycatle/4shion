<div
    x-data="{ open: false }"
    x-on:open-cart.window="open = true"
    x-on:cart-added.window="open = true"
    x-on:keydown.escape.window="open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
>
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-ink/40" x-on:click="open = false"></div>

    <aside
        x-cloak
        x-show="open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-paper"
        role="dialog"
        aria-modal="true"
        aria-label="Panier"
    >
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-5">
            <h2 class="text-xl">Panier ({{ $totals->itemCount }})</h2>
            <button type="button" x-on:click="open = false" aria-label="Fermer le panier">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
            </button>
        </div>

        @if ($items->isEmpty())
            <div class="flex flex-1 flex-col items-start justify-center gap-4 px-5">
                <p class="display text-5xl">Vide.</p>
                <p class="text-sm text-muted">Votre panier ne contient encore aucun article.</p>
                <a href="{{ route('shop.index') }}" wire:navigate x-on:click="open = false" class="btn-primary">Voir la boutique</a>
            </div>
        @else
            <ul class="flex-1 divide-y divide-line overflow-y-auto px-5">
                @foreach ($items as $item)
                    @php($variant = $item->variant)
                    <li class="flex gap-4 py-5" wire:key="drawer-item-{{ $item->id }}">
                        <a href="{{ route('shop.show', $variant->product) }}" wire:navigate x-on:click="open = false" class="block w-20 shrink-0">
                            <x-shop.thumb :product="$variant->product" class="flex aspect-[4/5]" />
                        </a>
                        <div class="flex flex-1 flex-col justify-between">
                            <div class="flex justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium">{{ $variant->product->name }}</p>
                                    <p class="mt-0.5 text-xs text-muted">{{ $variant->label() }}</p>
                                </div>
                                <p class="text-sm">{{ \App\Support\Money::format($variant->price() * $item->quantity) }}</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="inline-flex items-center border border-line bg-white">
                                    <button type="button" wire:click="decrement({{ $item->id }})" class="h-8 w-8" aria-label="Diminuer la quantité">−</button>
                                    <span class="w-7 text-center text-sm">{{ $item->quantity }}</span>
                                    <button type="button" wire:click="increment({{ $item->id }})" class="h-8 w-8" aria-label="Augmenter la quantité">+</button>
                                </div>
                                <button type="button" wire:click="remove({{ $item->id }})" class="text-xs text-muted underline underline-offset-4 hover:text-ink">Retirer</button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="shrink-0 space-y-4 border-t border-line p-5">
                @if ($error)
                    <p class="text-sm text-red-700" role="alert">{{ $error }}</p>
                @endif
                <x-shop.shipping-progress :totals="$totals" />
                <div class="flex items-baseline justify-between">
                    <span class="text-sm text-muted">Sous-total</span>
                    <span class="font-display text-xl font-bold">{{ \App\Support\Money::format($totals->subtotal - $totals->discount) }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('cart') }}" wire:navigate x-on:click="open = false" class="btn-outline">Voir le panier</a>
                    <a href="{{ route('checkout') }}" wire:navigate x-on:click="open = false" class="btn-primary">Commander</a>
                </div>
            </div>
        @endif
    </aside>
</div>
