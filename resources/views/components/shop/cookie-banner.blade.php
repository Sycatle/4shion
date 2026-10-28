{{-- Consent is stored in a first-party cookie readable by the server (@consent('analytics')). Nothing but strictly necessary cookies is set before a choice is made. --}}
<div
    x-data="{
        visible: false,
        customize: false,
        analytics: false,
        marketing: false,
        init() {
            this.visible = ! document.cookie.split('; ').some((c) => c.startsWith('cookie_consent='));
        },
        save(analytics, marketing) {
            const value = encodeURIComponent(JSON.stringify({ analytics, marketing }));
            document.cookie = `cookie_consent=${value}; Max-Age=${60 * 60 * 24 * 182}; Path=/; SameSite=Lax`;
            this.visible = false;
            this.customize = false;
        },
    }"
    x-on:open-cookie-settings.window="visible = true; customize = true"
    x-cloak
    x-show="visible"
    role="dialog"
    aria-labelledby="cookie-title"
    class="fixed inset-x-0 bottom-0 z-[60] border-t border-ink bg-paper p-5 shadow-lg"
>
    <div class="shell flex flex-wrap items-start justify-between gap-6">
        <div class="max-w-2xl text-sm">
            <p id="cookie-title" class="font-display text-xl font-bold">{{ __('Vos choix sur les cookies') }}</p>
            <p class="mt-2 text-muted">{{ __('Nous utilisons des cookies indispensables (panier, connexion, sécurité). Les cookies de mesure d’audience et de marketing ne sont déposés qu’avec votre accord, que vous pouvez modifier à tout moment.') }} <a href="{{ route('legal.privacy') }}" class="underline underline-offset-4">{{ __('En savoir plus') }}</a></p>

            <div x-show="customize" class="mt-4 space-y-3">
                <label class="flex items-center gap-3"><input type="checkbox" checked disabled class="border-ink text-ink"> {{ __('Indispensables (toujours actifs)') }}</label>
                <label class="flex items-center gap-3"><input type="checkbox" x-model="analytics" class="border-ink text-ink focus:ring-signal"> {{ __('Mesure d’audience') }}</label>
                <label class="flex items-center gap-3"><input type="checkbox" x-model="marketing" class="border-ink text-ink focus:ring-signal"> {{ __('Marketing') }}</label>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="btn-outline" x-show="! customize" x-on:click="customize = true">{{ __('Personnaliser') }}</button>
            <button type="button" class="btn-outline" x-show="customize" x-on:click="save(analytics, marketing)">{{ __('Enregistrer mes choix') }}</button>
            <button type="button" class="btn-outline" x-on:click="save(false, false)">{{ __('Tout refuser') }}</button>
            <button type="button" class="btn-primary" x-on:click="save(true, true)">{{ __('Tout accepter') }}</button>
        </div>
    </div>
</div>
