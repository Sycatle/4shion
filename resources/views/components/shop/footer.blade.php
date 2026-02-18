<footer class="mt-24 bg-ink text-paper">
    <div class="shell grid gap-12 py-16 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
            <x-shop.emblem class="h-24 w-auto text-paper/90" />
            <p class="mt-6 max-w-xs text-sm text-steel">Basiques techniques, taillés dans des matières épaisses. Une collection permanente, sans saison.</p>
        </div>
        <nav class="space-y-3 text-sm" aria-label="Boutique">
            <p class="font-display text-lg font-bold">Boutique</p>
            <a href="{{ route('shop.index') }}" wire:navigate class="block hover:underline">Tous les produits</a>
            <a href="{{ route('shop.index', ['sort' => 'newest']) }}" wire:navigate class="block hover:underline">Nouveautés</a>
        </nav>
        <nav class="space-y-3 text-sm" aria-label="Compte">
            <p class="font-display text-lg font-bold">Mon compte</p>
            <a href="{{ route('account.orders') }}" wire:navigate class="block hover:underline">Mes commandes</a>
            <a href="{{ route('account.addresses') }}" wire:navigate class="block hover:underline">Mes adresses</a>
            <a href="{{ route('profile') }}" wire:navigate class="block hover:underline">Mon profil</a>
        </nav>
        <div class="space-y-3 text-sm">
            <p class="font-display text-lg font-bold">Livraison et retours</p>
            <p class="text-steel">Livraison en {{ config('shop.shipping.delay') }}, offerte dès {{ \App\Support\Money::format(config('shop.shipping.free_threshold')) }}.</p>
            <p class="text-steel">Retours gratuits sous 30 jours.</p>
            <a href="mailto:{{ config('shop.company.email') }}" class="block underline underline-offset-4">{{ config('shop.company.email') }}</a>
        </div>
    </div>
    <div class="border-t border-paper/15">
        <div class="shell py-5 text-xs text-steel">&copy; {{ date('Y') }} {{ config('shop.company.name') }}. Tous droits réservés.</div>
    </div>
</footer>
