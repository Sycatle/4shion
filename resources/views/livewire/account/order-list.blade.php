<x-account.shell title="Mes commandes">
    @if ($orders->isEmpty())
        <div class="border border-line py-16 text-center">
            <p class="text-lg font-semibold">Vous n'avez pas encore passé de commande.</p>
            <a href="{{ route('shop.index') }}" wire:navigate class="btn-primary mt-6">Découvrir la boutique</a>
        </div>
    @else
        <ul class="divide-y divide-line border-y border-line">
            @foreach ($orders as $order)
                <li wire:key="order-{{ $order->id }}">
                    <a href="{{ route('account.orders.show', $order) }}" wire:navigate class="flex flex-wrap items-center justify-between gap-4 py-5 hover:bg-white/60">
                        <div>
                            <p class="text-sm font-medium">{{ $order->number }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $order->created_at->translatedFormat('j F Y') }} · {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('article', $order->items_count) }}</p>
                        </div>
                        <x-account.status-badge :status="$order->status" />
                        <p class="text-sm font-medium">{{ \App\Support\Money::format($order->total) }}</p>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-8">{{ $orders->links() }}</div>
    @endif
</x-account.shell>
