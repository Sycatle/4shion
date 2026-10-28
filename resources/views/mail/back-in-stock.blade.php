<x-mail::message>
# De retour en stock

L’article que vous attendiez est à nouveau disponible : **{{ $alert->variant->product->name }}** ({{ $alert->variant->label() }}).

Les quantités sont limitées, ne tardez pas.

<x-mail::button :url="route('shop.show', $alert->variant->product)">
Voir l’article
</x-mail::button>

L’équipe {{ config('app.name') }}
</x-mail::message>
