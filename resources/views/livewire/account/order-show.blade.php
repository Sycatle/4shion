<x-account.shell :title="'Commande '.$order->number">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-account.status-badge :status="$order->status" />
            <span class="text-sm text-muted">Passée le {{ $order->created_at->translatedFormat('j F Y à H:i') }}</span>
        </div>
        <a href="{{ route('account.orders') }}" wire:navigate class="text-sm underline underline-offset-4 hover:text-muted">Retour aux commandes</a>
    </div>

    @if (session('status'))
        <p class="mt-6 border border-ink bg-white px-4 py-3 text-sm" role="status">{{ session('status') }}</p>
    @endif

    <x-account.order-timeline :status="$order->status" class="mt-8" />

    @if ($order->status === \App\Enums\OrderStatus::Pending)
        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border border-ink bg-white p-5">
            <p class="text-sm">Cette commande attend son paiement. Le stock de vos articles est réservé pour le moment.</p>
            <button type="button" wire:click="pay" wire:loading.attr="disabled" class="btn-primary">Payer {{ \App\Support\Money::format($order->amountDue()) }}</button>
        </div>
        @if ($error)
            <p class="mt-3 text-sm text-red-700" role="alert">{{ $error }}</p>
        @endif
    @endif

    <div class="mt-8 grid gap-12 lg:grid-cols-[1fr_22rem]">
        <ul class="divide-y divide-line border-y border-line">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-4 py-4 text-sm" wire:key="item-{{ $item->id }}">
                    <span>
                        {{ $item->quantity }} × {{ $item->product_name }}
                        <span class="block text-sm text-muted">{{ $item->variant_label }}, réf. {{ $item->sku }}</span>
                        @if ($item->backordered_quantity > 0)
                            <span class="block text-sm text-muted">Précommande : {{ $item->backordered_quantity }} article(s) expédié(s) dès réception du stock.</span>
                        @endif
                        @if ($item->is_gift_card)
                            @foreach ($item->giftCards as $giftCard)
                                <span class="block text-sm">Code : <strong>{{ $giftCard->code }}</strong></span>
                            @endforeach
                        @endif
                    </span>
                    <span>{{ \App\Support\Money::format($item->total) }}</span>
                </li>
            @endforeach
        </ul>

        <aside class="space-y-8">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd>{{ \App\Support\Money::format($order->subtotal) }}</dd></div>
                @if ($order->discount_total > 0)
                    <div class="flex justify-between"><dt class="text-muted">{{ $order->discountLabel() }}</dt><dd>−{{ \App\Support\Money::format($order->discount_total) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-muted">Livraison</dt><dd>{{ $order->shipping_total === 0 ? 'Offerte' : \App\Support\Money::format($order->shipping_total) }}</dd></div>
                <div class="flex justify-between border-t border-line pt-4 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Money::format($order->total) }}</dd></div>
                @if ($order->gift_card_total > 0)
                    <div class="flex justify-between"><dt class="text-muted">Carte cadeau</dt><dd>−{{ \App\Support\Money::format($order->gift_card_total) }}</dd></div>
                    <div class="flex justify-between text-base font-semibold"><dt>Payé par carte</dt><dd>{{ \App\Support\Money::format($order->amountDue()) }}</dd></div>
                @endif
            </dl>

            <div class="text-sm">
                <p class="font-display text-2xl font-bold">Adresse de livraison</p>
                <address class="mt-3 not-italic leading-relaxed">
                    {{ $order->shipping_address->fullName() }}<br>
                    {{ $order->shipping_address->line1 }}<br>
                    @if ($order->shipping_address->line2){{ $order->shipping_address->line2 }}<br>@endif
                    {{ $order->shipping_address->postalCode }} {{ $order->shipping_address->city }}
                </address>
            </div>

            @if ($order->shipping_method_name)
                <p class="text-sm text-muted">Mode de livraison : {{ $order->shipping_method_name }}</p>
            @endif

            @if ($order->shipments->isNotEmpty())
                <div class="text-sm">
                    <p class="font-display text-2xl font-bold">Colis</p>
                    <ul class="mt-3 space-y-3">
                        @foreach ($order->shipments as $shipment)
                            <li wire:key="shipment-{{ $shipment->id }}">
                                Expédié le {{ $shipment->shipped_at->translatedFormat('j F Y') }}
                                @if ($shipment->tracking_number)
                                    <span class="block text-muted">{{ $shipment->carrier?->label() }} · {{ $shipment->tracking_number }}</span>
                                    @if ($shipment->trackingUrl())
                                        <a href="{{ $shipment->trackingUrl() }}" target="_blank" rel="noopener" class="underline underline-offset-4">Suivre le colis</a>
                                    @endif
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($order->returns->isNotEmpty())
                <div class="text-sm">
                    <p class="font-display text-2xl font-bold">Retours</p>
                    <ul class="mt-3 space-y-3">
                        @foreach ($order->returns as $return)
                            <li wire:key="return-{{ $return->id }}">
                                {{ $return->number }} · {{ $return->status->label() }}
                                @if ($return->status === \App\Enums\ReturnStatus::Refunded)
                                    <span class="block text-muted">Remboursé : {{ \App\Support\Money::format($return->refund_amount) }}</span>
                                    <a href="{{ route('account.returns.credit-note', [$order, $return]) }}" class="underline underline-offset-4">Télécharger l’avoir</a>
                                @elseif ($return->status === \App\Enums\ReturnStatus::Rejected && $return->decision_note)
                                    <span class="block text-muted">{{ $return->decision_note }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($order->isReturnable())
                <a href="{{ route('account.orders.return', $order) }}" wire:navigate class="btn-outline w-full">Retourner des articles</a>
                <p class="text-xs text-muted">Droit de rétractation de 14 jours après réception, retours acceptés jusqu’au {{ $order->returnDeadline()->translatedFormat('j F Y') }}.</p>
            @endif

            @if ($order->status->isPaid())
                <a href="{{ route('account.orders.invoice', $order) }}" class="btn-outline w-full">Télécharger la facture</a>
            @endif
        </aside>
    </div>
</x-account.shell>
