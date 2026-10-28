@props(['title', 'products'])

@if ($products->isNotEmpty())
    <section {{ $attributes->class('mt-20 border-t border-line pt-12') }}>
        <h2 class="text-4xl">{{ $title }}</h2>
        <div class="mt-8 grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-shop.product-card :product="$product" wire:key="{{ \Illuminate\Support\Str::slug($title) }}-{{ $product->id }}" />
            @endforeach
        </div>
    </section>
@endif
