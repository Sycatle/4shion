<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bons de préparation</title>
    <style>
        @page { margin: 36px 44px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #0E0E0E; }
        .slip { page-break-after: always; }
        .slip:last-child { page-break-after: auto; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6B6862; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        table.items { margin-top: 24px; }
        table.items th { text-align: left; font-size: 9px; text-transform: uppercase; border-bottom: 1px solid #0E0E0E; padding: 8px 4px; }
        table.items td { border-bottom: 1px solid #E2DED5; padding: 10px 4px; }
        .box { display: inline-block; width: 11px; height: 11px; border: 1px solid #0E0E0E; }
        .right { text-align: right; }
        .notes { margin-top: 24px; border: 1px solid #E2DED5; padding: 8px; }
    </style>
</head>
<body>
@foreach ($orders as $order)
    @php($address = $order->shipping_address)
    <section class="slip">
        <table>
            <tr>
                <td>
                    <h1>Bon de préparation</h1>
                    <p class="muted">Commande {{ $order->number }}<br>Passée le {{ $order->created_at->translatedFormat('d/m/Y') }}</p>
                </td>
                <td class="right">
                    <strong>{{ $address->fullName() }}</strong><br>
                    {{ $address->line1 }}<br>
                    @if ($address->line2){{ $address->line2 }}<br>@endif
                    {{ $address->postalCode }} {{ $address->city }}<br>
                    @if ($address->phone)Tél. {{ $address->phone }}@endif
                </td>
            </tr>
        </table>

        <table class="items">
            <thead><tr><th style="width: 24px"></th><th>Article</th><th>SKU</th><th class="right">Qté</th></tr></thead>
            <tbody>
            @foreach ($order->items->where('is_gift_card', false) as $item)
                <tr>
                    <td><span class="box"></span></td>
                    <td>{{ $item->product_name }}<br><span class="muted">{{ $item->variant_label }}</span></td>
                    <td>{{ $item->sku }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        @if ($order->notes->isNotEmpty())
            <div class="notes">
                <strong>Notes internes</strong>
                @foreach ($order->notes as $note)
                    <p>{{ $note->body }}</p>
                @endforeach
            </div>
        @endif
    </section>
@endforeach
</body>
</html>
