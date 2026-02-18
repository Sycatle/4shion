@props(['product'])

@php
    $inStock = $product->variants->contains(fn ($variant) => $variant->stock > 0);
    $colors = $product->variants->unique('color');
@endphp

<a href="{{ route('shop.show', $product) }}" wire:navigate {{ $attributes->class('group block') }}>
    <div class="relative">
        <x-shop.thumb :product="$product" class="flex aspect-[4/5]" />

        @unless ($inStock)
            <span class="absolute left-3 top-3 bg-ink px-2 py-1 text-xs font-medium text-paper">Épuisé</span>
        @endunless
    </div>

    <div class="mt-3 flex items-baseline justify-between gap-3">
        <h3 class="font-sans text-sm font-semibold group-hover:underline group-hover:underline-offset-4" style="font-stretch: 100%; letter-spacing: 0">{{ $product->name }}</h3>
        <p class="whitespace-nowrap text-sm">{{ \App\Support\Money::format($product->base_price) }}</p>
    </div>

    <div class="mt-2 flex items-center gap-1.5">
        @foreach ($colors as $variant)
            <span class="h-3 w-3 rounded-full border border-ink/20" style="background-color: {{ $variant->color_hex }}" title="{{ $variant->color }}"></span>
        @endforeach
    </div>
</a>
