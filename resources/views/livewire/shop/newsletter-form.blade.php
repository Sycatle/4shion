<div>
    <p class="font-display text-lg font-bold">{{ __('Newsletter') }}</p>
    @if ($submitted)
        <p class="mt-3 text-sm text-steel" role="status">{{ __('Un e-mail de confirmation vient de vous être envoyé : cliquez sur le lien pour finaliser votre inscription.') }}</p>
    @else
        <form wire:submit="subscribe" class="mt-3 space-y-2">
            <label for="newsletter-email" class="sr-only">{{ __('Votre e-mail') }}</label>
            <div class="flex gap-2">
                <input id="newsletter-email" type="email" wire:model="email" placeholder="{{ __('Votre e-mail') }}" autocomplete="email" class="field min-w-0 flex-1 text-ink" required>
                <button type="submit" wire:loading.attr="disabled" class="btn-primary shrink-0 bg-paper text-ink">OK</button>
            </div>
            @error('email') <p class="text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
            @if ($error) <p class="text-xs text-red-300" role="alert">{{ $error }}</p> @endif
            <p class="text-xs text-steel">{{ __('Nouveautés et offres, un e-mail par mois au plus. Désinscription en un clic.') }} <a href="{{ route('legal.privacy') }}" class="underline">{{ __('Confidentialité') }}</a></p>
        </form>
    @endif
</div>
