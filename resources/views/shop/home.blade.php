<x-layouts.shop title="Vêtements premium" description="4shion, basiques techniques en matières épaisses : hoodies, t-shirts, vestes, pantalons et accessoires.">
    <x-seo.json-ld :data="\App\Support\StructuredData::organization()" />
    <section class="grid lg:min-h-[40rem] lg:grid-cols-[5fr_7fr]">
        <div class="relative isolate overflow-hidden bg-concrete">
            <div class="relative z-10 flex h-full flex-col justify-between gap-16 px-5 py-10 sm:px-8 lg:pl-[max(2rem,calc((100vw-80rem)/2+2rem))] lg:pr-12">
                <h1 class="display text-[clamp(3.5rem,9vw,8rem)]">Matières épaisses.<br>Coupes droites.</h1>
                <div class="max-w-sm space-y-6">
                    <p class="text-base">Une collection permanente de basiques techniques, sans saison ni logo à rallonge.</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('shop.index') }}" wire:navigate class="btn-primary">Voir la boutique</a>
                        <a href="{{ route('shop.index', ['sort' => 'newest']) }}" wire:navigate class="btn-outline">Nouveautés</a>
                    </div>
                </div>
            </div>
            <x-shop.emblem class="pointer-events-none absolute -bottom-6 right-4 -z-10 h-[24rem] w-auto text-paper lg:h-[88%]" />
        </div>

        <div class="relative min-h-[26rem] bg-ink">
            @if (is_file(public_path('images/hero.webp')))
                <img src="{{ asset('images/hero.webp') }}" alt="Hoodie noir et pantalon cargo olive devant un mur en béton" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high">
            @endif
        </div>
    </section>

    <section class="shell mt-20">
        <h2 class="text-4xl sm:text-5xl">Les incontournables</h2>
        <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 lg:grid-cols-4 lg:gap-x-5">
            @foreach ($featured as $product)
                <x-shop.product-card :product="$product" />
            @endforeach
        </div>
    </section>

    <section class="shell mt-24">
        <div class="flex items-end justify-between gap-4">
            <h2 class="text-4xl sm:text-5xl">Dernières arrivées</h2>
            <a href="{{ route('shop.index', ['sort' => 'newest']) }}" wire:navigate class="text-sm font-medium underline underline-offset-4">Tout voir</a>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 lg:grid-cols-4 lg:gap-x-5">
            @foreach ($newest as $product)
                <x-shop.product-card :product="$product" />
            @endforeach
        </div>
    </section>

    <section class="mt-24" aria-labelledby="categories-title">
        <div class="shell">
            <h2 id="categories-title" class="text-4xl sm:text-5xl">Les catégories</h2>
        </div>
        <ul class="shell mt-8 flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 lg:grid lg:grid-cols-5 lg:overflow-visible lg:pb-0">
            @foreach ($categories as $category)
                @php($theme = $category->theme())
                <li class="w-[72%] shrink-0 snap-start sm:w-[44%] lg:w-auto">
                    <a href="{{ route('shop.category', $category->slug) }}" wire:navigate class="group block" style="background-color: {{ $theme['field'] }}; color: {{ $theme['ink'] }}">
                        <div class="aspect-[4/5] overflow-hidden">
                            @if ($image = $category->imageUrl())
                                <img src="{{ $image }}" alt="" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            @endif
                        </div>
                        <div class="flex items-end justify-between gap-3 p-4">
                            <h3 class="display text-3xl" style="letter-spacing: -0.01em">{{ $category->name }}</h3>
                            <div class="flex shrink-0 -space-x-2">
                                @foreach ($category->products as $product)
                                    <x-shop.thumb :product="$product" :theme="$theme" conversion="card" class="flex h-12 w-10 border border-paper" />
                                @endforeach
                            </div>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.shop>
