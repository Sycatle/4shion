<footer class="mt-24 border-t border-line">
    <div class="shell grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div class="space-y-3">
            <p class="text-xl font-semibold uppercase tracking-brand">4shion</p>
            <p class="max-w-xs text-sm text-muted">Vêtements premium aux lignes épurées, pensés pour durer.</p>
        </div>
        <div class="space-y-3 text-sm">
            <p class="eyebrow">Boutique</p>
            <a href="{{ route('shop.index') }}" wire:navigate class="block hover:text-muted">Tous les produits</a>
            <a href="{{ route('shop.index', ['sort' => 'newest']) }}" wire:navigate class="block hover:text-muted">Nouveautés</a>
        </div>
        <div class="space-y-3 text-sm">
            <p class="eyebrow">Mon compte</p>
            <a href="{{ route('account.orders') }}" wire:navigate class="block hover:text-muted">Mes commandes</a>
            <a href="{{ route('account.addresses') }}" wire:navigate class="block hover:text-muted">Mes adresses</a>
            <a href="{{ route('profile') }}" wire:navigate class="block hover:text-muted">Mon profil</a>
        </div>
        <div class="space-y-3 text-sm">
            <p class="eyebrow">Service client</p>
            <p class="text-muted">Livraison en 2 à 4 jours ouvrés.</p>
            <p class="text-muted">Retours gratuits sous 30 jours.</p>
        </div>
    </div>
    <div class="border-t border-line py-5 text-center text-xs text-muted">
        &copy; {{ date('Y') }} 4shion. Tous droits réservés.
    </div>
</footer>
