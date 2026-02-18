<div class="shell py-10">
    <x-shop.steps :current="1" />
    <h1 class="display mt-8 text-6xl sm:text-7xl">Panier</h1>

    @if (session('status'))
        <p class="mt-6 border border-ink bg-white px-4 py-3 text-sm" role="status">{{ session('status') }}</p>
    @endif

    @if ($items->isEmpty())
        <div class="mt-10 max-w-md">
            <p class="text-lg font-semibold">Votre panier est vide.</p>
            <p class="mt-1 text-sm text-muted">Ajoutez un article depuis la boutique pour le retrouver ici.</p>
            <a href="{{ route('shop.index') }}" wire:navigate class="btn-primary mt-6">Voir la boutique</a>
        </div>
    @else
        <div class="mt-10 grid gap-12 lg:grid-cols-[1fr_24rem]">
            <div>
                @if ($error)
                    <p class="mb-4 border border-ink bg-white px-4 py-3 text-sm" role="alert">{{ $error }}</p>
                @endif

                <ul class="divide-y divide-line border-y border-line">
                    @foreach ($items as $item)
                        @php($variant = $item->variant)
                        <li class="flex gap-5 py-6" wire:key="item-{{ $item->id }}">
                            <a href="{{ route('shop.show', $variant->product) }}" wire:navigate class="block w-24 shrink-0">
                                <x-shop.thumb :product="$variant->product" class="flex aspect-[4/5]" />
                            </a>
                            <div class="flex flex-1 flex-col justify-between">
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <a href="{{ route('shop.show', $variant->product) }}" wire:navigate class="text-sm font-semibold hover:underline">{{ $variant->product->name }}</a>
                                        <p class="mt-1 text-xs text-muted">{{ $variant->label() }}</p>
                                    </div>
                                    <p class="text-sm">{{ \App\Support\Money::format($variant->price() * $item->quantity) }}</p>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="inline-flex items-center border border-line bg-white">
                                        <button type="button" wire:click="decrement({{ $item->id }})" class="h-9 w-9" aria-label="Diminuer la quantité">−</button>
                                        <span class="w-8 text-center text-sm">{{ $item->quantity }}</span>
                                        <button type="button" wire:click="increment({{ $item->id }})" class="h-9 w-9" aria-label="Augmenter la quantité">+</button>
                                    </div>
                                    <button type="button" wire:click="remove({{ $item->id }})" class="text-sm text-muted underline underline-offset-4 hover:text-ink">Retirer</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <aside class="h-fit space-y-6 border border-line bg-white p-6">
                <h2 class="text-3xl">Récapitulatif</h2>

                <x-shop.shipping-progress :totals="$totals" />

                <div>
                    @if ($coupon && $totals->discount > 0)
                        <div class="flex items-center justify-between border border-dashed border-ink px-4 py-3 text-sm">
                            <span>Code <strong>{{ $coupon->code }}</strong> appliqué</span>
                            <button type="button" wire:click="removeCoupon" class="text-sm underline underline-offset-4">Retirer</button>
                        </div>
                    @else
                        <form wire:submit="applyCoupon" class="flex gap-2">
                            <input type="text" wire:model="couponCode" placeholder="Code promo" class="field" aria-label="Code promo">
                            <button type="submit" class="btn-outline shrink-0 px-4">Appliquer</button>
                        </form>
                        @if ($couponError)
                            <p class="mt-2 text-xs text-red-700" role="alert">{{ $couponError }}</p>
                        @endif
                    @endif
                </div>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd>{{ \App\Support\Money::format($totals->subtotal) }}</dd></div>
                    @if ($totals->discount > 0)
                        <div class="flex justify-between"><dt class="text-muted">Réduction</dt><dd>−{{ \App\Support\Money::format($totals->discount) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-muted">Livraison</dt><dd>{{ $totals->shipping === 0 ? 'Offerte' : \App\Support\Money::format($totals->shipping) }}</dd></div>
                    <div class="flex justify-between border-t border-line pt-4 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Money::format($totals->total) }}</dd></div>
                </dl>

                <a href="{{ route('checkout') }}" wire:navigate class="btn-primary w-full">Passer à la livraison</a>
            </aside>
        </div>
    @endif
</div>
