<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

final class GenerateInvoice
{
    public function __invoke(Order $order): DomPdf
    {
        $order->loadMissing('items', 'user');

        return Pdf::loadView('invoices.invoice', ['order' => $order])->setPaper('a4');
    }

    public static function filename(Order $order): string
    {
        return "facture-{$order->number}.pdf";
    }
}
