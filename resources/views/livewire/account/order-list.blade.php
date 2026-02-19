<x-account.shell title="Mes commandes">
    @if ($orders->isEmpty())
        <div class="max-w-md">
            <p class="text-lg font-semibold">Vous n'avez pas encore passé de commande.</p>
            <p class="mt-1 text-sm text-muted">Vos commandes et leurs factures apparaîtront ici.</p>
            <a href="{{ route('shop.index') }}" wire:navigate class="btn-primary mt-6">Voir la boutique</a>
        </div>
    @else
        <ul class="divide-y divide-line border-y border-line">
            @foreach ($orders as $order)
                <li wire:key="order-{{ $order->id }}">
                    <a href="{{ route('account.orders.show', $order) }}" wire:navigate class="flex flex-wrap items-center justify-between gap-4 py-5 group hover:bg-white/60">
                        <div>
                            <p class="text-sm font-semibold">{{ $order->number }}</p>
                            <p class="mt-1 text-sm text-muted">{{ $order->created_at->translatedFormat('j F Y') }}, {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('article', $order->items_count) }}</p>
                        </div>
                        <x-account.status-badge :status="$order->status" />
                        <p class="flex items-center gap-3 text-sm font-semibold">{{ \App\Support\Money::format($order->total) }}<svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></p>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-8">{{ $orders->links() }}</div>
    @endif
</x-account.shell>
