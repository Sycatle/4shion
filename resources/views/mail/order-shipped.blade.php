<x-mail::message>
# Votre commande est en route

Bonne nouvelle {{ $order->user->name }}, la commande **{{ $order->number }}** vient d’être expédiée à l’adresse suivante :

{{ $order->shipping_address['first_name'] }} {{ $order->shipping_address['last_name'] }}<br>
{{ $order->shipping_address['line1'] }}<br>
@if (! empty($order->shipping_address['line2']))
{{ $order->shipping_address['line2'] }}<br>
@endif
{{ $order->shipping_address['postal_code'] }} {{ $order->shipping_address['city'] }}

<x-mail::button :url="route('account.orders.show', $order)">
Voir ma commande
</x-mail::button>

L’équipe {{ config('app.name') }}
</x-mail::message>
