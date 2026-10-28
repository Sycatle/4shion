@props(['title'])

@php
    $links = [
        ['account.orders', 'Mes commandes'],
        ['account.wishlist', 'Mes favoris'],
        ['account.addresses', 'Mes adresses'],
        ['profile', 'Mon profil'],
    ];
@endphp

<div class="shell py-10">
    <h1 class="display text-6xl sm:text-7xl">{{ $title }}</h1>

    <nav class="mt-8 flex items-center gap-8 overflow-x-auto border-b border-line text-sm font-medium" aria-label="Compte">
        @foreach ($links as [$route, $label])
            @php($active = request()->routeIs($route.'*'))
            <a href="{{ route($route) }}" wire:navigate @if ($active) aria-current="page" @endif @class(['whitespace-nowrap border-b-2 pb-4', 'border-signal text-ink' => $active, 'border-transparent text-muted hover:text-ink' => ! $active])>{{ $label }}</a>
        @endforeach
        <form method="POST" action="{{ route('logout') }}" class="ml-auto pb-4">
            @csrf
            <button type="submit" class="whitespace-nowrap text-muted underline underline-offset-4 hover:text-ink">Déconnexion</button>
        </form>
    </nav>

    <div class="mt-10">
        {{ $slot }}
    </div>
</div>
