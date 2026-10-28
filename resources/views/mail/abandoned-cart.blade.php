<x-mail::message>
# Votre panier vous attend

Vous avez laissé ces articles dans votre panier :

@foreach ($cart->items as $item)
- **{{ $item->variant->product->name }}** ({{ $item->variant->label() }}) × {{ $item->quantity }}
@endforeach

@if ($couponCode)
Pour vous aider à finaliser, utilisez le code **{{ $couponCode }}** au panier.

@endif
<x-mail::button :url="route('cart')">
Reprendre mon panier
</x-mail::button>

Les quantités ne sont pas réservées : certains articles peuvent partir vite.

L’équipe {{ config('app.name') }}
</x-mail::message>
