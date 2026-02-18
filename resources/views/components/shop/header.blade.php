@php
    $categories = \App\Models\Category::query()->orderBy('position')->get(['id', 'name', 'slug']);
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-line bg-paper/95 backdrop-blur">
    <div class="bg-ink px-4 py-2 text-center text-xs text-paper">
        Livraison offerte dès {{ \App\Support\Money::format(config('shop.shipping.free_threshold')) }} d'achat
    </div>

    <div class="shell grid h-16 grid-cols-[1fr_auto_1fr] items-center gap-6">
        <div class="flex items-center">
            <button type="button" class="-ml-1 p-1 lg:hidden" x-on:click="open = true" aria-label="Ouvrir le menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/></svg>
            </button>
            <nav class="hidden items-center gap-6 text-sm font-medium lg:flex" aria-label="Catégories">
                @foreach ($categories as $category)
                    <a href="{{ route('shop.index', ['category' => $category->slug]) }}" wire:navigate class="whitespace-nowrap hover:underline hover:underline-offset-4">{{ $category->name }}</a>
                @endforeach
            </nav>
        </div>

        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-1" aria-label="4shion, accueil">
            <x-shop.emblem class="h-6 w-auto" />
            <span class="font-display text-2xl font-extrabold leading-none" style="font-stretch: 75%">shion</span>
        </a>

        <div class="flex items-center justify-end gap-4">
            <button type="button" x-on:click="$dispatch('open-search')" aria-label="Rechercher" class="p-1">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
            </button>
            @auth
                <a href="{{ route('account.orders') }}" wire:navigate aria-label="Mon compte" class="p-1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/></svg>
                </a>
            @else
                <a href="{{ route('login') }}" wire:navigate class="hidden text-sm font-medium hover:underline hover:underline-offset-4 sm:block">Connexion</a>
                <a href="{{ route('login') }}" wire:navigate aria-label="Connexion" class="p-1 sm:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/></svg>
                </a>
            @endauth
            <livewire:shop.cart-counter />
        </div>
    </div>

    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex flex-col overflow-y-auto bg-paper lg:hidden" x-on:keydown.escape.window="open = false">
        <div class="shell flex h-16 shrink-0 items-center justify-between">
            <x-shop.emblem class="h-6 w-auto" />
            <button type="button" x-on:click="open = false" aria-label="Fermer le menu" class="p-1">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
            </button>
        </div>
        <nav class="shell flex flex-1 flex-col gap-1 pt-6" aria-label="Menu principal">
            <button type="button" x-on:click="open = false; $dispatch('open-search')" class="mb-4 flex items-center gap-3 border border-line bg-white px-4 py-3 text-left text-sm text-muted">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                Rechercher un produit
            </button>
            <a href="{{ route('shop.index') }}" wire:navigate class="display py-1 text-5xl">Tout</a>
            @foreach ($categories as $category)
                <a href="{{ route('shop.index', ['category' => $category->slug]) }}" wire:navigate class="display py-1 text-5xl">{{ $category->name }}</a>
            @endforeach
        </nav>
        <div class="shell flex shrink-0 items-center gap-6 border-t border-line py-5 text-sm">
            @auth
                <a href="{{ route('account.orders') }}" wire:navigate class="font-medium">Mon compte</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-muted underline underline-offset-4">Déconnexion</button>
                </form>
            @else
                <a href="{{ route('login') }}" wire:navigate class="font-medium">Connexion</a>
                <a href="{{ route('register') }}" wire:navigate class="text-muted underline underline-offset-4">Créer un compte</a>
            @endauth
        </div>
    </div>
</header>
