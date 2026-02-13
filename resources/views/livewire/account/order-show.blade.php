<x-account.shell :title="'Commande '.$order->number">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-account.status-badge :status="$order->status" />
            <span class="text-sm text-muted">Passée le {{ $order->created_at->translatedFormat('j F Y à H:i') }}</span>
        </div>
        <a href="{{ route('account.orders') }}" wire:navigate class="text-xs uppercase tracking-[0.14em] underline underline-offset-4 hover:text-muted">← Retour aux commandes</a>
    </div>

    <div class="mt-8 grid gap-12 lg:grid-cols-[1fr_22rem]">
        <ul class="divide-y divide-line border-y border-line">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-4 py-4 text-sm" wire:key="item-{{ $item->id }}">
                    <span>
                        {{ $item->quantity }} × {{ $item->product_name }}
                        <span class="block text-xs text-muted">{{ $item->variant_label }} · réf. {{ $item->sku }}</span>
                    </span>
                    <span>{{ \App\Support\Money::format($item->total) }}</span>
                </li>
            @endforeach
        </ul>

        <aside class="space-y-8">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd>{{ \App\Support\Money::format($order->subtotal) }}</dd></div>
                @if ($order->discount_total > 0)
                    <div class="flex justify-between"><dt class="text-muted">Réduction {{ $order->coupon_code }}</dt><dd>−{{ \App\Support\Money::format($order->discount_total) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-muted">Livraison</dt><dd>{{ $order->shipping_total === 0 ? 'Offerte' : \App\Support\Money::format($order->shipping_total) }}</dd></div>
                <div class="flex justify-between border-t border-line pt-4 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Money::format($order->total) }}</dd></div>
            </dl>

            <div class="text-sm">
                <p class="eyebrow">Adresse de livraison</p>
                <address class="mt-3 not-italic leading-relaxed">
                    {{ $order->shipping_address['first_name'] }} {{ $order->shipping_address['last_name'] }}<br>
                    {{ $order->shipping_address['line1'] }}<br>
                    @if ($order->shipping_address['line2']){{ $order->shipping_address['line2'] }}<br>@endif
                    {{ $order->shipping_address['postal_code'] }} {{ $order->shipping_address['city'] }}
                </address>
            </div>
        </aside>
    </div>
</x-account.shell>
