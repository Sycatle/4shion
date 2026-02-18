<div class="shell max-w-2xl py-16" @if ($order->status === \App\Enums\OrderStatus::Pending) wire:init="refreshStatus" wire:poll.3s="refreshStatus" @endif>
    @if ($order->status->isPaid())
        <h1 class="display text-6xl sm:text-7xl">Commande confirmée.</h1>
        <p class="mt-4 text-base">Commande {{ $order->number }}. Un e-mail de confirmation vous a été envoyé, avec la facture en pièce jointe.</p>
        <p class="mt-1 text-base text-muted">Nous préparons votre colis : livraison en {{ config('shop.shipping.delay') }}.</p>
    @elseif ($order->status === \App\Enums\OrderStatus::Pending)
        <h1 class="display text-6xl sm:text-7xl">Paiement en cours.</h1>
        <p class="mt-4 text-base">Commande {{ $order->number }}. Nous attendons la confirmation de votre banque, cette page se met à jour toute seule.</p>
        <p class="mt-1 text-base text-muted">Si rien ne change après quelques minutes, retrouvez la commande dans <a href="{{ route('account.orders.show', $order) }}" wire:navigate class="underline underline-offset-4">votre compte</a> ou écrivez-nous à <a href="mailto:{{ config('shop.company.email') }}" class="underline underline-offset-4">{{ config('shop.company.email') }}</a>.</p>
    @else
        <h1 class="display text-6xl sm:text-7xl">Commande non finalisée.</h1>
        <p class="mt-4 text-base">La commande {{ $order->number }} est {{ mb_strtolower($order->status->label()) }}. Votre panier vous attend si vous souhaitez recommencer.</p>
    @endif

    <ul class="mt-10 divide-y divide-line border-y border-line text-sm">
        @foreach ($order->items as $item)
            <li class="flex justify-between gap-4 py-3">
                <span>{{ $item->quantity }} × {{ $item->product_name }}<span class="block text-sm text-muted">{{ $item->variant_label }}</span></span>
                <span>{{ \App\Support\Money::format($item->total) }}</span>
            </li>
        @endforeach
    </ul>
    <p class="mt-4 flex justify-between text-base font-semibold"><span>Total</span><span>{{ \App\Support\Money::format($order->total) }}</span></p>

    <div class="mt-10 flex flex-wrap gap-3">
        @if ($order->status->isPaid())
            <a href="{{ route('account.orders.show', $order) }}" wire:navigate class="btn-primary">Voir la commande</a>
            <a href="{{ route('account.orders.invoice', $order) }}" class="btn-outline">Télécharger la facture</a>
        @else
            <a href="{{ route('cart') }}" wire:navigate class="btn-primary">Retour au panier</a>
        @endif
        <a href="{{ route('shop.index') }}" wire:navigate class="btn-outline">Continuer mes achats</a>
    </div>
</div>
