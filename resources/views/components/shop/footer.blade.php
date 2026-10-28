<footer class="mt-24 bg-ink text-paper">
    <div class="shell grid gap-12 py-16 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
            <x-shop.emblem class="h-24 w-auto text-paper/90" />
            <p class="mt-6 max-w-xs text-sm text-steel">{{ __('Basiques techniques, taillés dans des matières épaisses. Une collection permanente, sans saison.') }}</p>
        </div>
        <nav class="space-y-3 text-sm" aria-label="{{ __('Boutique') }}">
            <p class="font-display text-lg font-bold">{{ __('Boutique') }}</p>
            <a href="{{ route('shop.index') }}" wire:navigate class="block hover:underline">{{ __('Tous les produits') }}</a>
            <a href="{{ route('shop.index', ['sort' => 'newest']) }}" wire:navigate class="block hover:underline">{{ __('Nouveautés') }}</a>
        </nav>
        <nav class="space-y-3 text-sm" aria-label="{{ __('Compte') }}">
            <p class="font-display text-lg font-bold">{{ __('Mon compte') }}</p>
            <a href="{{ route('account.orders') }}" wire:navigate class="block hover:underline">{{ __('Mes commandes') }}</a>
            <a href="{{ route('account.wishlist') }}" wire:navigate class="block hover:underline">{{ __('Mes favoris') }}</a>
            <a href="{{ route('account.addresses') }}" wire:navigate class="block hover:underline">{{ __('Mes adresses') }}</a>
            <a href="{{ route('profile') }}" wire:navigate class="block hover:underline">{{ __('Mon profil') }}</a>
        </nav>
        <div class="space-y-3 text-sm">
            <p class="font-display text-lg font-bold">{{ __('Livraison et retours') }}</p>
            <p class="text-steel">{{ __('Livraison en :delay, offerte dès :amount.', ['delay' => config('shop.shipping.delay'), 'amount' => \App\Support\Money::format(config('shop.shipping.free_threshold'))]) }}</p>
            <p class="text-steel">{{ __('Retours gratuits sous :days jours.', ['days' => config('shop.returns.window_days')]) }}</p>
            <a href="mailto:{{ config('shop.company.email') }}" class="block underline underline-offset-4">{{ config('shop.company.email') }}</a>
            <livewire:shop.newsletter-form class="pt-4" />
        </div>
    </div>
    <div class="border-t border-paper/15">
        <div class="shell flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-5 text-xs text-steel">
            <p>&copy; {{ date('Y') }} {{ config('shop.company.name') }}. {{ __('Tous droits réservés.') }}</p>
            <nav class="flex flex-wrap gap-x-5 gap-y-1" aria-label="{{ __('Informations légales') }}">
                <a href="{{ route('legal.terms') }}" wire:navigate class="hover:text-paper">{{ __('Conditions de vente') }}</a>
                <a href="{{ route('legal.notice') }}" wire:navigate class="hover:text-paper">{{ __('Mentions légales') }}</a>
                <a href="{{ route('legal.privacy') }}" wire:navigate class="hover:text-paper">{{ __('Confidentialité') }}</a>
                <a href="{{ route('contact') }}" wire:navigate class="hover:text-paper">{{ __('Contact') }}</a>
                <a href="{{ route('journal.index') }}" wire:navigate class="hover:text-paper">{{ __('Journal') }}</a>
                <button type="button" x-data x-on:click="$dispatch('open-cookie-settings')" class="hover:text-paper">{{ __('Gérer mes cookies') }}</button>
            </nav>
            <nav class="flex gap-3" aria-label="{{ __('Langue') }}">
                @foreach (config('app.supported_locales') as $code => $label)
                    <a href="{{ route('locale', $code) }}" @if (app()->getLocale() === $code) aria-current="true" class="font-semibold text-paper" @else class="hover:text-paper" @endif lang="{{ $code }}" hreflang="{{ $code }}">{{ strtoupper($code) }}</a>
                @endforeach
            </nav>
        </div>
    </div>
</footer>
