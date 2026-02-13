<x-account.shell title="Mes adresses">
    <div class="grid gap-6 sm:grid-cols-2">
        @foreach ($addresses as $address)
            <article class="border border-line bg-white p-6 text-sm" wire:key="address-{{ $address->id }}">
                <div class="flex items-start justify-between gap-3">
                    <p class="font-medium">{{ $address->fullName() }}</p>
                    @if ($address->is_default)
                        <span class="bg-ink px-2 py-0.5 text-[10px] uppercase tracking-[0.1em] text-paper">Par défaut</span>
                    @endif
                </div>
                <address class="mt-3 not-italic leading-relaxed text-muted">
                    {{ $address->line1 }}<br>
                    @if ($address->line2){{ $address->line2 }}<br>@endif
                    {{ $address->postal_code }} {{ $address->city }}
                </address>
                <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-xs uppercase tracking-[0.14em]">
                    <button type="button" wire:click="edit({{ $address->id }})" class="underline underline-offset-4 hover:text-muted">Modifier</button>
                    @unless ($address->is_default)
                        <button type="button" wire:click="makeDefault({{ $address->id }})" class="underline underline-offset-4 hover:text-muted">Par défaut</button>
                    @endunless
                    <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="Supprimer cette adresse ?" class="text-red-700 underline underline-offset-4">Supprimer</button>
                </div>
            </article>
        @endforeach

        @unless ($showForm)
            <button type="button" wire:click="create" class="flex min-h-40 items-center justify-center border border-dashed border-sand-dark text-sm uppercase tracking-[0.14em] text-muted hover:border-ink hover:text-ink">
                + Ajouter une adresse
            </button>
        @endunless
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mt-10 grid max-w-2xl gap-5 border border-line bg-white p-6 sm:grid-cols-2 sm:p-8">
            <h2 class="eyebrow sm:col-span-2">{{ $editingId ? 'Modifier l\'adresse' : 'Nouvelle adresse' }}</h2>

            @foreach ([
                ['firstName', 'Prénom', 'sm:col-span-1'],
                ['lastName', 'Nom', 'sm:col-span-1'],
                ['line1', 'Adresse', 'sm:col-span-2'],
                ['line2', 'Complément d\'adresse (facultatif)', 'sm:col-span-2'],
                ['postalCode', 'Code postal', 'sm:col-span-1'],
                ['city', 'Ville', 'sm:col-span-1'],
                ['phone', 'Téléphone (facultatif)', 'sm:col-span-2'],
            ] as [$field, $label, $span])
                <div class="{{ $span }}">
                    <label for="address-{{ $field }}" class="mb-2 block text-xs text-muted">{{ $label }}</label>
                    <input id="address-{{ $field }}" type="text" wire:model="{{ $field }}" class="field">
                    @error($field) <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="flex gap-3 sm:col-span-2">
                <button type="submit" class="btn-primary">Enregistrer</button>
                <button type="button" wire:click="cancel" class="btn-outline">Annuler</button>
            </div>
        </form>
    @endif
</x-account.shell>
