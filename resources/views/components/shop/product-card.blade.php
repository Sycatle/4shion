@props(['product'])

@php
    $inStock = $product->variants->contains(fn ($variant) => $variant->stock > 0);
    $colors = $product->variants->unique('color');
    $image = $product->imageUrl('card');
@endphp

<a href="{{ route('shop.show', $product) }}" wire:navigate {{ $attributes->class('group block') }}>
    <div class="relative aspect-[4/5] overflow-hidden bg-[#EBE7DE]">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            <div class="flex h-full w-full items-center justify-center text-5xl font-semibold uppercase tracking-brand text-sand">4</div>
        @endif

        @unless ($inStock)
            <span class="absolute left-3 top-3 bg-ink px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.14em] text-paper">Épuisé</span>
        @endunless
    </div>

    <div class="mt-4 flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-medium">{{ $product->name }}</h3>
            <p class="mt-1 text-xs text-muted">{{ $product->category->name }}</p>
        </div>
        <p class="whitespace-nowrap text-sm">{{ \App\Support\Money::format($product->base_price) }}</p>
    </div>

    <div class="mt-3 flex gap-1.5">
        @foreach ($colors as $variant)
            <span class="h-3 w-3 rounded-full border border-line" style="background-color: {{ $variant->color_hex }}" title="{{ $variant->color }}"></span>
        @endforeach
    </div>
</a>
