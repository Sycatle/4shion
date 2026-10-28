@php
    use App\Support\Money;

    $company = config('shop.company');
    $order = $return->order;
    $address = $order->billing_address;
    $vatRate = (float) $order->vat_rate;
    $vat = \App\Models\Order::vatIncludedIn($return->refund_amount, $vatRate);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Avoir {{ $return->creditNoteNumber() }}</title>
    <style>
        @page { margin: 40px 48px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #0E0E0E; }
        h1 { font-size: 22px; letter-spacing: 4px; text-transform: uppercase; margin: 0; }
        .muted { color: #6B6862; }
        .row { width: 100%; }
        .row td { vertical-align: top; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 28px; }
        table.items th { text-align: left; font-size: 9px; text-transform: uppercase; border-bottom: 1px solid #0E0E0E; padding: 8px 4px; }
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
                <p style="font-size: 16px; margin: 0;">AVOIR</p>
                <p>N° {{ $return->creditNoteNumber() }}<br>Date : {{ ($return->refunded_at ?? now())->translatedFormat('d/m/Y') }}<br>Sur la facture / commande {{ $order->number }}</p>
            </td>
        </tr>
    </table>

    <p style="margin-top: 24px;"><strong>{{ $address->fullName() }}</strong><br>{{ $address->line1 }}<br>{{ $address->postalCode }} {{ $address->city }}</p>

    <table class="items">
        <thead><tr><th>Article retourné</th><th class="right">Qté</th><th class="right">Prix unitaire TTC</th></tr></thead>
        <tbody>
        @foreach ($return->items as $line)
            <tr>
                <td>{{ $line->orderItem->product_name }}<br><span class="muted">{{ $line->orderItem->variant_label }}</span></td>
                <td class="right">{{ $line->quantity }}</td>
                <td class="right">{{ Money::format($line->orderItem->unit_price) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Total HT remboursé</td><td class="right">{{ Money::format($return->refund_amount - $vat) }}</td></tr>
        <tr><td>TVA ({{ rtrim(rtrim(number_format($vatRate, 2, ',', ''), '0'), ',') }} %)</td><td class="right">{{ Money::format($vat) }}</td></tr>
        <tr class="grand"><td>Total TTC remboursé</td><td class="right">{{ Money::format($return->refund_amount) }}</td></tr>
    </table>

    <p class="footer">Avoir émis suite au retour {{ $return->number }}. Remboursement effectué sur le moyen de paiement utilisé pour la commande.</p>
</body>
</html>
