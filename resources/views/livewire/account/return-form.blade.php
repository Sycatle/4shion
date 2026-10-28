<x-account.shell :title="'Retour — '.$order->number">
    <div class="max-w-2xl">
        <p class="text-sm text-muted">Vous avez jusqu’au {{ $deadline->translatedFormat('j F Y') }} pour demander un retour. Le droit légal de rétractation est de 14 jours après réception ; nous vous accordons {{ config('shop.returns.window_days') }} jours.</p>

        @if ($items->isEmpty())
            <p class="mt-8 text-sm">Tous les articles de cette commande font déjà l’objet d’un retour.</p>
        @else
            <form wire:submit="submit" class="mt-8 space-y-8">
                @if ($error)
                    <p class="border border-ink bg-white px-4 py-3 text-sm" role="alert">{{ $error }}</p>
                @endif

                <fieldset>
                    <legend class="mb-3 font-display text-2xl font-bold">Articles à retourner</legend>
                    <ul class="divide-y divide-line border-y border-line">
                        @foreach ($items as $item)
                            <li class="flex items-center justify-between gap-4 py-4 text-sm" wire:key="return-item-{{ $item->id }}">
                                <label for="qty-{{ $item->id }}">
                                    {{ $item->product_name }}
                                    <span class="block text-muted">{{ $item->variant_label }}, jusqu’à {{ $item->returnableQuantity() }}</span>
                                </label>
                                <input id="qty-{{ $item->id }}" type="number" min="0" max="{{ $item->returnableQuantity() }}" wire:model="quantities.{{ $item->id }}" placeholder="0" class="field w-20">
                            </li>
                        @endforeach
                    </ul>
                </fieldset>

                <div>
                    <label for="reason" class="mb-2 block text-sm font-medium">Motif</label>
                    <select id="reason" wire:model="reason" class="field">
                        @foreach (\App\Enums\ReturnReason::cases() as $reason)
                            <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                    @error('reason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="comment" class="mb-2 block text-sm font-medium">Commentaire (facultatif)</label>
                    <textarea id="comment" rows="3" wire:model="comment" class="field"></textarea>
                    @error('comment') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" class="btn-primary">Envoyer ma demande</button>
            </form>
        @endif
    </div>
</x-account.shell>
