<x-mail::message>
# Votre commande est en route

Bonne nouvelle {{ $order->user->name }}, la commande **{{ $order->number }}** vient d’être expédiée à l’adresse suivante :

{{ $order->shipping_address->fullName() }}<br>
{{ $order->shipping_address->line1 }}<br>
@if ($order->shipping_address->line2)
{{ $order->shipping_address->line2 }}<br>
@endif
{{ $order->shipping_address->postalCode }} {{ $order->shipping_address->city }}

@foreach ($order->shipments as $shipment)
@if ($shipment->tracking_number)
Suivi {{ $shipment->carrier?->label() }} : **{{ $shipment->tracking_number }}**@if ($shipment->trackingUrl()) ([suivre le colis]({{ $shipment->trackingUrl() }}))@endif

@endif
@endforeach

<x-mail::button :url="route('account.orders.show', $order)">
Voir ma commande
</x-mail::button>

L’équipe {{ config('app.name') }}
</x-mail::message>
