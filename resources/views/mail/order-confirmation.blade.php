@php use App\Support\Money; @endphp
<x-mail::message>
# Merci pour votre commande, {{ $order->user->name }}

Nous avons bien reçu votre paiement pour la commande **{{ $order->number }}**. Votre facture est jointe à cet email.

<x-mail::table>
| Article | Qté | Total |
| :------ | :-: | ----: |
@foreach ($order->items as $item)
| {{ $item->product_name }} — {{ $item->variant_label }} | {{ $item->quantity }} | {{ Money::format($item->total) }} |
@endforeach
</x-mail::table>

Sous-total : {{ Money::format($order->subtotal) }}
@if ($order->discount_total > 0)
<br>Remise{{ $order->coupon_code ? " ({$order->coupon_code})" : '' }} : −{{ Money::format($order->discount_total) }}
@endif
<br>Livraison : {{ $order->shipping_total > 0 ? Money::format($order->shipping_total) : 'Offerte' }}
<br>**Total TTC : {{ Money::format($order->total) }}**

**Adresse de livraison**<br>
{{ $order->shipping_address['first_name'] }} {{ $order->shipping_address['last_name'] }}<br>
{{ $order->shipping_address['line1'] }}<br>
@if (! empty($order->shipping_address['line2']))
{{ $order->shipping_address['line2'] }}<br>
@endif
{{ $order->shipping_address['postal_code'] }} {{ $order->shipping_address['city'] }}

<x-mail::button :url="route('account.orders.show', $order)">
Suivre ma commande
</x-mail::button>

À très vite,<br>
L’équipe {{ config('app.name') }}
</x-mail::message>
