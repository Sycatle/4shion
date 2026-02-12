<x-layouts.shop title="Vêtements premium" description="4shion, vêtements premium aux lignes épurées : hoodies, t-shirts, vestes et accessoires.">
    <section class="relative isolate overflow-hidden bg-ink text-paper">
        @if (is_file(public_path('images/hero.webp')))
            <img src="{{ asset('images/hero.webp') }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-60">
        @endif
        <div class="shell flex min-h-[34rem] flex-col justify-end pb-16 pt-32 lg:min-h-[40rem]">
            <p class="eyebrow text-sand">Collection permanente</p>
            <h1 class="mt-4 max-w-3xl text-5xl leading-[1.02] sm:text-7xl">L'essentiel, sans compromis.</h1>
            <p class="mt-6 max-w-lg text-base text-paper/70">Des pièces aux lignes épurées, coupées dans des matières épaisses et pensées pour durer.</p>
            <div class="mt-10 flex flex-wrap gap-4">
                <a href="{{ route('shop.index') }}" wire:navigate class="btn bg-paper text-ink hover:bg-sand">Découvrir la boutique</a>
                <a href="{{ route('shop.index', ['sort' => 'newest']) }}" wire:navigate class="btn border border-paper/40 text-paper hover:bg-paper hover:text-ink">Nouveautés</a>
            </div>
        </div>
    </section>

    <section class="shell mt-20">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($categories as $category)
                <a href="{{ route('shop.index', ['category' => $category->slug]) }}" wire:navigate class="group flex aspect-[4/3] items-end border border-line bg-white p-5 transition hover:bg-ink hover:text-paper lg:aspect-[3/4]">
                    <span class="text-sm font-medium uppercase tracking-[0.14em]">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    @if ($featured->isNotEmpty())
        <section class="shell mt-24">
            <div class="mb-8 flex items-end justify-between">
                <div>
                    <p class="eyebrow">Sélection</p>
                    <h2 class="mt-2 text-3xl">Les incontournables</h2>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4">
                @foreach ($featured as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="shell mt-24">
        <div class="mb-8 flex items-end justify-between">
            <div>
                <p class="eyebrow">Nouveautés</p>
                <h2 class="mt-2 text-3xl">Dernières arrivées</h2>
            </div>
            <a href="{{ route('shop.index') }}" wire:navigate class="text-xs font-medium uppercase tracking-[0.14em] underline underline-offset-4 hover:text-muted">Tout voir</a>
        </div>
        <div class="grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4">
            @foreach ($newest as $product)
                <x-shop.product-card :product="$product" />
            @endforeach
        </div>
    </section>

    <section class="shell mt-24 grid gap-px border border-line bg-line sm:grid-cols-3">
        @foreach ([['Matières premium', 'Coton épais, laine mélangée et finitions soignées.'], ['Livraison rapide', 'Expédition sous 48 h, offerte dès 100 €.'], ['Retours gratuits', '30 jours pour changer d\'avis, sans frais.']] as [$title, $text])
            <div class="bg-paper p-8">
                <h3 class="text-sm font-semibold uppercase tracking-[0.14em]">{{ $title }}</h3>
                <p class="mt-3 text-sm text-muted">{{ $text }}</p>
            </div>
        @endforeach
    </section>
</x-layouts.shop>
