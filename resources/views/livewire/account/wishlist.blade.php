<x-account.shell title="Mes favoris">
    @if ($products->isEmpty())
        <div class="max-w-md">
            <p class="text-lg font-semibold">Aucun favori pour le moment.</p>
            <p class="mt-1 text-sm text-muted">Ajoutez des articles depuis leur fiche pour les retrouver ici.</p>
            <a href="{{ route('shop.index') }}" wire:navigate class="btn-primary mt-6">Voir la boutique</a>
        </div>
    @else
        <div class="mb-8 flex flex-wrap items-center gap-3 text-sm">
            <label for="share-url" class="text-muted">Lien à partager</label>
            <input id="share-url" type="text" readonly value="{{ $shareUrl }}" class="field w-auto min-w-0 flex-1 sm:max-w-md" onfocus="this.select()">
        </div>

        <div class="grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4">
            @foreach ($products as $product)
                <div wire:key="wish-{{ $product->id }}">
                    <x-shop.product-card :product="$product" />
                    <button type="button" wire:click="remove({{ $product->id }})" class="mt-3 text-xs text-muted underline underline-offset-4 hover:text-ink">Retirer</button>
                </div>
            @endforeach
        </div>
    @endif
</x-account.shell>
