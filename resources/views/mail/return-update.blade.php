<x-mail::message>
# Retour {{ $return->number }}

Commande **{{ $return->order->number }}** : {{ $return->status->label() }}.

@switch($return->status)
@case(\App\Enums\ReturnStatus::Requested)
Nous étudions votre demande et revenons vers vous très vite.
@break
@case(\App\Enums\ReturnStatus::Approved)
Renvoyez les articles dans leur emballage d’origine, accompagnés de votre numéro de retour **{{ $return->number }}**, à :

{{ config('shop.company.name') }}<br>
{{ config('shop.company.address') }}<br>
{{ config('shop.company.city') }}
@break
@case(\App\Enums\ReturnStatus::Rejected)
Nous ne pouvons pas donner suite à cette demande.@if ($return->decision_note) Motif : {{ $return->decision_note }}@endif
@break
@case(\App\Enums\ReturnStatus::Received)
Nous vérifions les articles et procédons au remboursement.
@break
@case(\App\Enums\ReturnStatus::Refunded)
Un remboursement de **{{ \App\Support\Money::format($return->refund_amount) }}** a été effectué sur votre moyen de paiement (délai bancaire : quelques jours). Votre avoir {{ $return->creditNoteNumber() }} est disponible dans votre compte.
@break
@endswitch

<x-mail::button :url="route('account.orders.show', $return->order)">
Voir ma commande
</x-mail::button>

L’équipe {{ config('app.name') }}
</x-mail::message>
