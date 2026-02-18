@props(['product', 'conversion' => 'card', 'theme' => null])

@php($theme ??= $product->category->theme())

<div {{ $attributes->class('overflow-hidden')->merge(['style' => 'background-color: '.$theme['field']]) }}>
    @if ($image = $product->imageUrl($conversion))
        <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover mix-blend-multiply">
    @else
        <x-shop.emblem class="m-auto h-1/3 w-auto opacity-30" style="color: {{ $theme['ink'] }}" />
    @endif
</div>
