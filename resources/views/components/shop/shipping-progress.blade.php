@props(['totals'])

@php
    $threshold = $totals->freeThreshold ?: config('shop.shipping.free_threshold');
    $current = $totals->subtotal - $totals->discount;
    $remaining = max(0, $threshold - $current);
    $percent = min(100, (int) round($current / $threshold * 100));
@endphp

<div {{ $attributes }}>
    <p class="text-sm">
        @if ($remaining > 0 && $totals->shipping > 0)
            Plus que <strong class="font-semibold">{{ \App\Support\Money::format($remaining) }}</strong> pour la livraison offerte.
        @else
            La livraison est offerte sur cette commande.
        @endif
    </p>
    <div class="mt-2 h-1 bg-concrete" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" aria-label="Progression vers la livraison offerte">
        <div class="h-full bg-signal transition-[width] duration-300" style="width: {{ $percent }}%"></div>
    </div>
</div>
