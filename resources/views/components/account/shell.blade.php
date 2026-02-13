@props(['title'])

@php
    $links = [
        ['account.orders', 'Mes commandes'],
        ['account.addresses', 'Mes adresses'],
        ['profile', 'Mon profil'],
    ];
@endphp

<div class="shell py-10">
    <p class="eyebrow">Mon compte</p>
    <h1 class="mt-2 text-3xl sm:text-4xl">{{ $title }}</h1>

    <nav class="mt-8 flex gap-8 overflow-x-auto border-b border-line text-xs font-medium uppercase tracking-[0.14em]" aria-label="Compte">
        @foreach ($links as [$route, $label])
            <a href="{{ route($route) }}" wire:navigate @class(['whitespace-nowrap pb-4', 'border-b-2 border-ink' => request()->routeIs($route.'*'), 'text-muted hover:text-ink' => ! request()->routeIs($route.'*')])>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="mt-10">
        {{ $slot }}
    </div>
</div>
