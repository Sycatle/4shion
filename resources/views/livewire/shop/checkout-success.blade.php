<div class="shell max-w-2xl py-16" @if ($order->status === \App\Enums\OrderStatus::Pending) wire:init="refreshStatus" wire:poll.3s="refreshStatus" @endif>
    @if ($order->status->isPaid())
        <p class="eyebrow">Commande {{ $order->number }}</p>
        <h1 class="mt-3 text-4xl">Merci pour votre commande.</h1>
        <p class="mt-4 text-muted">Un e-mail de confirmation vous a été envoyé. Nous préparons votre colis.</p>
    @elseif ($order->status === \App\Enums\OrderStatus::Pending)
        <p class="eyebrow">Commande {{ $order->number }}</p>
        <h1 class="mt-3 text-4xl">Confirmation du paiement…</h1>
        <p class="mt-4 text-muted">Nous attendons la confirmation de votre banque. Cette page se met à jour automatiquement.</p>
    @else
        <p class="eyebrow">Commande {{ $order->number }}</p>
        <h1 class="mt-3 text-4xl">Cette commande n'a pas été finalisée.</h1>
        <p class="mt-4 text-muted">Statut : {{ $order->status->label() }}.</p>
    @endif

    <ul class="mt-10 divide-y divide-line border-y border-line text-sm">
        @foreach ($order->items as $item)
            <li class="flex justify-between gap-4 py-3">
                <span>{{ $item->quantity }} × {{ $item->product_name }}<span class="block text-xs text-muted">{{ $item->variant_label }}</span></span>
                <span>{{ \App\Support\Money::format($item->total) }}</span>
            </li>
        @endforeach
    </ul>
    <p class="mt-4 flex justify-between text-base font-semibold"><span>Total</span><span>{{ \App\Support\Money::format($order->total) }}</span></p>

    <a href="{{ route('shop.index') }}" wire:navigate class="btn-primary mt-10">Continuer mes achats</a>
</div>
