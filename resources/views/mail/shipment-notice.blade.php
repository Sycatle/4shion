<x-mail::message>
# Une partie de votre commande est en route

Bonjour {{ $shipment->order->user->name }}, un premier colis de la commande **{{ $shipment->order->number }}** vient de partir. Le reste suivra dès qu’il sera prêt.

@foreach ($shipment->items()->with('orderItem')->get() as $line)
- {{ $line->quantity }} × {{ $line->orderItem->product_name }} ({{ $line->orderItem->variant_label }})
@endforeach

@if ($shipment->tracking_number)
Suivi {{ $shipment->carrier?->label() }} : **{{ $shipment->tracking_number }}**
@endif

@if ($shipment->trackingUrl())
<x-mail::button :url="$shipment->trackingUrl()">
Suivre mon colis
</x-mail::button>
@endif

<x-mail::button :url="route('account.orders.show', $shipment->order)">
Voir ma commande
</x-mail::button>

L’équipe {{ config('app.name') }}
</x-mail::message>
