<?php

namespace App\Actions\Orders;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

class GenerateInvoice
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
