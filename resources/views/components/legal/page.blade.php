@props(['title', 'intro' => null])

<x-layouts.shop :title="$title">
    <div class="shell grid gap-10 py-12 lg:grid-cols-[14rem_1fr] lg:gap-16">
        <nav class="text-sm lg:sticky lg:top-32 lg:self-start" aria-label="Informations légales">
            <ul class="space-y-2">
                @foreach ([
                    'legal.terms' => 'Conditions de vente',
                    'legal.notice' => 'Mentions légales',
                    'legal.privacy' => 'Confidentialité',
                    'contact' => 'Contact',
                ] as $route => $label)
                    <li><a href="{{ route($route) }}" wire:navigate @class(['hover:underline', 'font-semibold underline underline-offset-4' => request()->routeIs($route)])>{{ $label }}</a></li>
                @endforeach
            </ul>
        </nav>

        <article class="max-w-prose">
            <h1 class="display text-6xl sm:text-7xl">{{ $title }}</h1>
            @if ($intro)
                <p class="mt-4 text-base text-muted">{{ $intro }}</p>
            @endif
            <div class="mt-10 space-y-8 text-base leading-relaxed [&_h2]:mb-3 [&_h2]:text-3xl [&_p+p]:mt-3 [&_a]:underline [&_a]:underline-offset-4">
                {{ $slot }}
            </div>
        </article>
    </div>
</x-layouts.shop>
