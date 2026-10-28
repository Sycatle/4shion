@php use App\Support\Money; @endphp
<x-mail::message>
# Vos cartes cadeaux

Merci {{ $order->user->name }} ! Voici les cartes cadeaux achetées avec la commande **{{ $order->number }}**. Transmettez le code à la personne de votre choix : il s’utilise dans le panier, en une ou plusieurs fois.

<x-mail::table>
| Code | Montant | Valable jusqu’au |
|:-----|--------:|-----------------:|
@foreach ($giftCards as $giftCard)
| **{{ $giftCard->code }}** | {{ Money::format($giftCard->initial_value) }} | {{ $giftCard->expires_at?->format('d/m/Y') ?? '—' }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('shop.index')">
Découvrir la boutique
</x-mail::button>

L’équipe {{ config('app.name') }}
</x-mail::message>
