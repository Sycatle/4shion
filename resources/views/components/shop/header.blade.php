@php
    $categories = \App\Models\Category::query()->orderBy('position')->get(['id', 'name', 'slug']);
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-line bg-paper/90 backdrop-blur">
    <div class="bg-ink px-4 py-2 text-center text-[11px] uppercase tracking-brand text-paper">
        Livraison offerte dès {{ \App\Support\Money::format(config('shop.shipping.free_threshold')) }} d'achat
    </div>

    <div class="shell flex h-16 items-center justify-between gap-6">
        <button type="button" class="lg:hidden" x-on:click="open = true" aria-label="Ouvrir le menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/></svg>
        </button>

        <nav class="hidden items-center gap-8 text-xs font-medium uppercase tracking-[0.14em] lg:flex" aria-label="Catégories">
            <a href="{{ route('shop.index') }}" wire:navigate class="hover:text-muted">Boutique</a>
            @foreach ($categories->take(3) as $category)
                <a href="{{ route('shop.index', ['category' => $category->slug]) }}" wire:navigate class="hover:text-muted">{{ $category->name }}</a>
            @endforeach
        </nav>

        <a href="{{ route('home') }}" wire:navigate class="text-xl font-semibold uppercase tracking-brand lg:absolute lg:left-1/2 lg:-translate-x-1/2">
            4shion
        </a>

        <div class="flex items-center gap-5">
            <a href="{{ route('shop.index') }}" wire:navigate aria-label="Rechercher">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
            </a>
            @auth
                <a href="{{ route('profile') }}" wire:navigate aria-label="Mon compte">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/></svg>
                </a>
            @else
                <a href="{{ route('login') }}" wire:navigate class="text-xs font-medium uppercase tracking-[0.14em] hover:text-muted">Connexion</a>
            @endauth
            <livewire:shop.cart-counter />
        </div>
    </div>

    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-paper lg:hidden">
        <div class="shell flex h-16 items-center justify-between">
            <span class="text-xl font-semibold uppercase tracking-brand">4shion</span>
            <button type="button" x-on:click="open = false" aria-label="Fermer le menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
            </button>
        </div>
        <nav class="shell flex flex-col gap-6 pt-8 text-2xl font-semibold tracking-tight">
            <a href="{{ route('shop.index') }}" wire:navigate>Toute la boutique</a>
            @foreach ($categories as $category)
                <a href="{{ route('shop.index', ['category' => $category->slug]) }}" wire:navigate>{{ $category->name }}</a>
            @endforeach
        </nav>
    </div>
</header>
