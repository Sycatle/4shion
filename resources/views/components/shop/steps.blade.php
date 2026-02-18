@props(['current'])

<ol class="flex items-center gap-3 text-sm" aria-label="Étapes de la commande">
    @foreach (['Panier', 'Livraison', 'Paiement'] as $index => $label)
        @php($step = $index + 1)
        <li class="flex items-center gap-3" @if ($step === $current) aria-current="step" @endif>
            <span @class(['flex h-6 w-6 items-center justify-center text-xs font-semibold', 'bg-ink text-paper' => $step <= $current, 'border border-line text-muted' => $step > $current])>{{ $step }}</span>
            <span @class(['font-semibold' => $step === $current, 'text-muted' => $step !== $current])>{{ $label }}</span>
            @unless ($loop->last)
                <span class="h-px w-6 bg-line sm:w-10" aria-hidden="true"></span>
            @endunless
        </li>
    @endforeach
</ol>
