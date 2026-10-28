<x-layouts.shop :title="'Les favoris de '.\Illuminate\Support\Str::before($owner->name, ' ')" description="Une sélection d’articles 4shion.">
    <div class="shell py-10">
        <h1 class="display text-6xl sm:text-7xl">Favoris de {{ \Illuminate\Support\Str::before($owner->name, ' ') }}</h1>

        @if ($products->isEmpty())
            <p class="mt-8 text-sm text-muted">Cette liste est vide pour le moment.</p>
        @else
            <div class="mt-10 grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.shop>
