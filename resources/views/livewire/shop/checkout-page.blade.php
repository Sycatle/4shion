<div class="shell py-10">
    <x-shop.steps :current="2" />
    <h1 class="display mt-8 text-6xl sm:text-7xl">Livraison</h1>

    <div class="mt-10 grid gap-12 lg:grid-cols-[1fr_24rem]">
        <form wire:submit="submit" class="space-y-8" x-data x-on:checkout-invalid.window="$nextTick(() => $el.querySelector('[aria-invalid=true]')?.focus())">
            @if ($error)
                <p class="border border-ink bg-white px-4 py-3 text-sm" role="alert">{{ $error }}</p>
            @endif

            @guest
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium">E-mail</label>
                    <input id="email" type="email" wire:model="email" autocomplete="email" class="field" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    <p class="mt-2 text-sm text-muted">Pas besoin de compte pour commander : nous en créons un avec cet e-mail pour suivre votre commande, et vous envoyons un lien pour choisir un mot de passe. Déjà client ? <a href="{{ route('login') }}" wire:navigate class="underline underline-offset-4">Connectez-vous</a>.</p>
                </div>
            @endguest

            @if ($addresses->isNotEmpty())
                <div>
                    <p class="font-display text-2xl font-bold">Mes adresses</p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($addresses as $address)
                            <button type="button" wire:click="useSavedAddress({{ $address->id }})" aria-pressed="{{ $savedAddressId === $address->id ? 'true' : 'false' }}" @class(['border px-4 py-3 text-left text-sm', 'border-ink bg-white ring-1 ring-ink' => $savedAddressId === $address->id, 'border-line' => $savedAddressId !== $address->id])>
                                <span class="block font-medium">{{ $address->fullName() }}</span>
                                <span class="block text-muted">{{ $address->line1 }}, {{ $address->postal_code }} {{ $address->city }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <fieldset class="grid gap-5 sm:grid-cols-2">
                <legend class="mb-4 font-display text-2xl font-bold">Adresse de livraison</legend>

                @foreach ([
                    ['firstName', 'Prénom', 'given-name', 'sm:col-span-1', 'text'],
                    ['lastName', 'Nom', 'family-name', 'sm:col-span-1', 'text'],
                    ['line1', 'Adresse', 'address-line1', 'sm:col-span-2', 'text'],
                    ['line2', 'Complément d\'adresse (facultatif)', 'address-line2', 'sm:col-span-2', 'text'],
                    ['postalCode', 'Code postal', 'postal-code', 'sm:col-span-1', 'numeric'],
                    ['city', 'Ville', 'address-level2', 'sm:col-span-1', 'text'],
                    ['phone', 'Téléphone (facultatif)', 'tel', 'sm:col-span-2', 'tel'],
                ] as [$field, $label, $autocomplete, $span, $mode])
                    <div class="{{ $span }}">
                        <label for="{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
                        <input id="{{ $field }}" type="text" inputmode="{{ $mode }}" wire:model="{{ $field }}" autocomplete="{{ $autocomplete }}" class="field" @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        @error($field) <p id="{{ $field }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <p class="text-sm text-muted sm:col-span-2">Livraison en France métropolitaine, en {{ config('shop.shipping.delay') }}.</p>
            </fieldset>

            @if ($shippingMethods->isNotEmpty() && ! $totals->giftCardsOnly)
                <fieldset>
                    <legend class="mb-3 font-display text-2xl font-bold">Mode de livraison</legend>
                    <div class="space-y-3">
                        @foreach ($shippingMethods as $method)
                            @php($cost = $totals->subtotal - $totals->discount >= $method->freeThreshold() ? 0 : $method->price)
                            <label @class(['flex cursor-pointer items-center justify-between gap-4 border p-4 text-sm', 'border-ink bg-white ring-1 ring-ink' => $shippingMethodId === $method->id, 'border-line' => $shippingMethodId !== $method->id]) wire:key="method-{{ $method->id }}">
                                <span class="flex items-center gap-3">
                                    <input type="radio" wire:model.live="shippingMethodId" value="{{ $method->id }}" class="border-ink text-ink focus:ring-signal">
                                    <span>
                                        <span class="block font-medium">{{ $method->name }}</span>
                                        @if ($method->delay)<span class="block text-muted">{{ $method->delay }}</span>@endif
                                    </span>
                                </span>
                                <span class="font-medium">{{ $cost === 0 ? 'Offerte' : \App\Support\Money::format($cost) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" wire:model="saveAddress" class="border-ink text-ink focus:ring-signal">
                Enregistrer cette adresse pour mes prochaines commandes
            </label>

            <div>
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" wire:model="acceptTerms" class="mt-0.5 border-ink text-ink focus:ring-signal" @error('acceptTerms') aria-invalid="true" aria-describedby="acceptTerms-error" @enderror>
                    <span>J'ai lu et j'accepte les <a href="{{ route('legal.terms') }}" target="_blank" class="underline underline-offset-4">conditions de vente</a>.</span>
                </label>
                @error('acceptTerms') <p id="acceptTerms-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" class="btn-primary w-full sm:w-auto">
                @if ($totals->amountDue() === 0)
                    <span wire:loading.remove>Valider la commande</span>
                    <span wire:loading>Validation…</span>
                @else
                    <span wire:loading.remove>Payer {{ \App\Support\Money::format($totals->amountDue()) }}</span>
                    <span wire:loading>Redirection vers le paiement…</span>
                @endif
            </button>
            @if ($totals->amountDue() > 0)
                <p class="text-sm text-muted">Étape suivante : le paiement par carte, sur la page sécurisée de Stripe. Nous ne conservons aucune donnée bancaire.</p>
            @else
                <p class="text-sm text-muted">Votre carte cadeau couvre la totalité de la commande : aucun paiement par carte bancaire n'est nécessaire.</p>
            @endif
        </form>

        <aside class="h-fit space-y-5 border border-line bg-white p-6">
            <h2 class="text-3xl">Votre commande</h2>
            <ul class="divide-y divide-line text-sm">
                @foreach ($items as $item)
                    <li class="flex justify-between gap-4 py-3">
                        <span>{{ $item->quantity }} × {{ $item->variant->product->name }}<span class="block text-sm text-muted">{{ $item->variant->label() }}</span></span>
                        <span>{{ \App\Support\Money::format($item->variant->price() * $item->quantity) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="space-y-3 border-t border-line pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd>{{ \App\Support\Money::format($totals->subtotal) }}</dd></div>
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
        </aside>
    </div>
</div>
