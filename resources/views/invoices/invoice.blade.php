@php
    use App\Support\Money;

    $company = config('shop.company');
    $shipping = $order->billing_address;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $order->number }}</title>
    <style>
        @page { margin: 40px 48px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #0E0E0E; }
        h1 { font-size: 22px; letter-spacing: 4px; text-transform: uppercase; margin: 0; }
        .muted { color: #6B6862; }
        .row { width: 100%; }
        .row td { vertical-align: top; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 28px; }
        table.items th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #0E0E0E; padding: 8px 4px; }
        table.items td { border-bottom: 1px solid #E2DED5; padding: 9px 4px; }
        .right { text-align: right; }
        table.totals { width: 45%; margin-left: 55%; margin-top: 18px; border-collapse: collapse; }
        table.totals td { padding: 4px; }
        table.totals tr.grand td { border-top: 1px solid #0E0E0E; font-weight: bold; font-size: 12px; padding-top: 8px; }
        .footer { margin-top: 48px; font-size: 8px; color: #6B6862; }
    </style>
</head>
<body>
    <table class="row">
        <tr>
            <td>
                <h1>4shion</h1>
                <p class="muted">{{ $company['name'] }}<br>{{ $company['address'] }}<br>{{ $company['city'] }}<br>SIRET {{ $company['siret'] }}<br>TVA {{ $company['vat_number'] }}</p>
            </td>
            <td class="right">
                <p style="font-size: 16px; margin: 0;">FACTURE</p>
                <p>N° {{ $order->number }}<br>Date : {{ ($order->paid_at ?? $order->created_at)->translatedFormat('d/m/Y') }}</p>
            </td>
        </tr>
    </table>

    <table class="row" style="margin-top: 24px;">
        <tr>
            <td>
                <p class="muted" style="margin: 0 0 4px;">FACTURÉ À</p>
                {{ $shipping->fullName() }}<br>
                {{ $shipping->line1 }}<br>
                @if ($shipping->line2){{ $shipping->line2 }}<br>@endif
                {{ $shipping->postalCode }} {{ $shipping->city }}
            </td>
            <td class="right">
                <p class="muted" style="margin: 0 0 4px;">PAIEMENT</p>
                Carte bancaire (Stripe)<br>
                {{ $order->paid_at?->translatedFormat('d/m/Y') }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="right">Qté</th>
                <th class="right">Prix unitaire TTC</th>
                <th class="right">Total TTC</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}<br><span class="muted">{{ $item->variant_label }} · {{ $item->sku }}</span></td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ Money::format($item->unit_price) }}</td>
                    <td class="right">{{ Money::format($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Sous-total</td><td class="right">{{ Money::format($order->subtotal) }}</td></tr>
        @if ($order->discount_total > 0)
            <tr><td>Réduction {{ $order->coupon_code }}</td><td class="right">−{{ Money::format($order->discount_total) }}</td></tr>
        @endif
        <tr><td>Livraison</td><td class="right">{{ $order->shipping_total === 0 ? 'Offerte' : Money::format($order->shipping_total) }}</td></tr>
        <tr class="grand"><td>Total TTC</td><td class="right">{{ Money::format($order->total) }}</td></tr>
        <tr><td class="muted">dont TVA ({{ \Illuminate\Support\Number::format((float) $order->vat_rate, locale: 'fr') }} %)</td><td class="right muted">{{ Money::format($order->vat_total) }}</td></tr>
    </table>

    <p class="footer">{{ $company['name'] }} · SIRET {{ $company['siret'] }} · TVA {{ $company['vat_number'] }} · {{ $company['email'] }}</p>
</body>
</html>
