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
                                        @if (! $variant->product->is_gift_card && $item->quantity > $variant->stock)
                                            @php($preordered = $item->quantity - $variant->stock)
                                            <p class="mt-1 text-xs text-muted">
                                                Précommande{{ $variant->stock > 0 ? " ({$preordered} sur {$item->quantity})" : '' }}{{ $variant->preorder_available_on ? ', disponible à partir du '.$variant->preorder_available_on->translatedFormat('j F Y') : '' }}
                                            </p>
                                        @endif
                                    </div>
                                    <p class="text-sm">
                                        {{ \App\Support\Money::format($variant->price() * $item->quantity) }}
                                        @if ($variant->isOnSale())
                                            <span class="block text-xs text-muted line-through">{{ \App\Support\Money::format($variant->regularPrice() * $item->quantity) }}</span>
                                        @endif
                                    </p>
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

                @unless ($totals->giftCardsOnly)
                    <x-shop.shipping-progress :totals="$totals" />
                @endunless

                <div>
                    @if ($totals->appliedCoupon)
                        <div class="flex items-center justify-between border border-dashed border-ink px-4 py-3 text-sm">
                            <span>Code <strong>{{ $totals->appliedCoupon->code }}</strong> appliqué</span>
                            <button type="button" wire:click="removeCoupon" class="text-sm underline underline-offset-4">Retirer</button>
                        </div>
                    @else
                        @if ($coupon)
                            <div class="mb-2 flex items-center justify-between border border-dashed border-line px-4 py-3 text-sm">
                                <span>Code <strong>{{ $coupon->code }}</strong> non appliqué</span>
                                <button type="button" wire:click="removeCoupon" class="text-sm underline underline-offset-4">Retirer</button>
                            </div>
                        @endif
                        <form wire:submit="applyCoupon" class="flex gap-2">
                            <input type="text" wire:model="couponCode" placeholder="Code promo" class="field" aria-label="Code promo">
                            <button type="submit" class="btn-outline shrink-0 px-4">Appliquer</button>
                        </form>
                    @endif
                    @if ($couponError)
                        <p class="mt-2 text-xs text-red-700" role="alert">{{ $couponError }}</p>
                    @elseif ($totals->couponNotice)
                        <p class="mt-2 text-xs text-muted" role="status">{{ $totals->couponNotice }}</p>
                    @endif
                </div>

                <div>
                    @if ($giftCard)
                        <div class="flex items-center justify-between border border-dashed border-ink px-4 py-3 text-sm">
                            <span>Carte cadeau <strong>{{ $giftCard->code }}</strong> · solde {{ \App\Support\Money::format($giftCard->balance) }}</span>
                            <button type="button" wire:click="removeGiftCard" class="text-sm underline underline-offset-4">Retirer</button>
                        </div>
                    @else
                        <form wire:submit="applyGiftCard" class="flex gap-2">
                            <input type="text" wire:model="giftCardCode" placeholder="Carte cadeau" class="field" aria-label="Code de carte cadeau">
                            <button type="submit" class="btn-outline shrink-0 px-4">Utiliser</button>
                        </form>
                        @if ($giftCardError)
                            <p class="mt-2 text-xs text-red-700" role="alert">{{ $giftCardError }}</p>
                        @endif
                    @endif
                </div>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd>{{ \App\Support\Money::format($totals->subtotal) }}</dd></div>
                    @if ($totals->saleSavings > 0)
                        <div class="flex justify-between"><dt class="text-muted">Dont économies soldes</dt><dd>{{ \App\Support\Money::format($totals->saleSavings) }}</dd></div>
                    @endif
                    @if ($totals->discount > 0)
                        <div class="flex justify-between"><dt class="text-muted">{{ $totals->discountLabel() }}</dt><dd>−{{ \App\Support\Money::format($totals->discount) }}</dd></div>
                    @endif
                    @unless ($totals->giftCardsOnly)
                        <div class="flex justify-between"><dt class="text-muted">Livraison</dt><dd>{{ $totals->shipping === 0 ? 'Offerte' : \App\Support\Money::format($totals->shipping) }}</dd></div>
                    @endunless
                    <div class="flex justify-between border-t border-line pt-4 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Money::format($totals->total) }}</dd></div>
                    @if ($totals->giftCard > 0)
                        <div class="flex justify-between"><dt class="text-muted">Carte cadeau</dt><dd>−{{ \App\Support\Money::format($totals->giftCard) }}</dd></div>
                        <div class="flex justify-between text-base font-semibold"><dt>Reste à payer</dt><dd>{{ \App\Support\Money::format($totals->amountDue()) }}</dd></div>
                    @endif
                </dl>

                <a href="{{ route('checkout') }}" wire:navigate class="btn-primary w-full">Passer à la livraison</a>
            </aside>
        </div>

        <x-shop.product-row :title="__('Pour compléter votre panier')" :products="$suggestions" />
    @endif
</div>
