<div class="shell py-10">
    <h1 class="text-3xl sm:text-4xl">Finaliser la commande</h1>

    <div class="mt-10 grid gap-12 lg:grid-cols-[1fr_24rem]">
        <form wire:submit="submit" class="space-y-8">
            @if ($error)
                <p class="border border-ink bg-white px-4 py-3 text-sm" role="alert">{{ $error }}</p>
            @endif

            @if ($addresses->isNotEmpty())
                <div>
                    <p class="eyebrow">Mes adresses</p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($addresses as $address)
                            <button type="button" wire:click="useSavedAddress({{ $address->id }})" @class(['border px-4 py-3 text-left text-xs', 'border-ink bg-white' => $savedAddressId === $address->id, 'border-line' => $savedAddressId !== $address->id])>
                                <span class="block font-medium">{{ $address->fullName() }}</span>
                                <span class="block text-muted">{{ $address->line1 }}, {{ $address->postal_code }} {{ $address->city }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <fieldset class="grid gap-5 sm:grid-cols-2">
                <legend class="eyebrow mb-4">Adresse de livraison</legend>

                @foreach ([
                    ['firstName', 'Prénom', 'given-name', 'sm:col-span-1'],
                    ['lastName', 'Nom', 'family-name', 'sm:col-span-1'],
                    ['line1', 'Adresse', 'address-line1', 'sm:col-span-2'],
                    ['line2', 'Complément d\'adresse (facultatif)', 'address-line2', 'sm:col-span-2'],
                    ['postalCode', 'Code postal', 'postal-code', 'sm:col-span-1'],
                    ['city', 'Ville', 'address-level2', 'sm:col-span-1'],
                    ['phone', 'Téléphone (facultatif)', 'tel', 'sm:col-span-2'],
                ] as [$field, $label, $autocomplete, $span])
                    <div class="{{ $span }}">
                        <label for="{{ $field }}" class="mb-2 block text-xs text-muted">{{ $label }}</label>
                        <input id="{{ $field }}" type="text" wire:model="{{ $field }}" autocomplete="{{ $autocomplete }}" class="field">
                        @error($field) <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <p class="text-xs text-muted sm:col-span-2">Livraison en France métropolitaine.</p>
            </fieldset>

            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" wire:model="saveAddress" class="border-line text-ink focus:ring-0">
                Enregistrer cette adresse pour mes prochaines commandes
            </label>

            <button type="submit" wire:loading.attr="disabled" class="btn-primary w-full sm:w-auto">
                <span wire:loading.remove>Payer {{ \App\Support\Money::format($totals->total) }}</span>
                <span wire:loading>Redirection vers le paiement…</span>
            </button>
            <p class="text-xs text-muted">Paiement sécurisé par Stripe. Vous serez redirigé vers la page de paiement.</p>
        </form>

        <aside class="h-fit space-y-5 border border-line bg-white p-6">
            <h2 class="text-sm font-semibold uppercase tracking-[0.14em]">Votre commande</h2>
            <ul class="divide-y divide-line text-sm">
                @foreach ($items as $item)
                    <li class="flex justify-between gap-4 py-3">
                        <span>{{ $item->quantity }} × {{ $item->variant->product->name }}<span class="block text-xs text-muted">{{ $item->variant->label() }}</span></span>
                        <span>{{ \App\Support\Money::format($item->variant->price() * $item->quantity) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="space-y-3 border-t border-line pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd>{{ \App\Support\Money::format($totals->subtotal) }}</dd></div>
                @if ($totals->discount > 0)
                    <div class="flex justify-between"><dt class="text-muted">Réduction</dt><dd>−{{ \App\Support\Money::format($totals->discount) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-muted">Livraison</dt><dd>{{ $totals->shipping === 0 ? 'Offerte' : \App\Support\Money::format($totals->shipping) }}</dd></div>
                <div class="flex justify-between border-t border-line pt-4 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Money::format($totals->total) }}</dd></div>
            </dl>
        </aside>
    </div>
</div>
