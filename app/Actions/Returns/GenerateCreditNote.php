<?php

declare(strict_types=1);

namespace App\Actions\Returns;

use App\Models\OrderReturn;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

final class GenerateCreditNote
{
    public function __invoke(OrderReturn $return): DomPdf
    {
        $return->loadMissing('order.user', 'items.orderItem');

        return Pdf::loadView('invoices.credit-note', ['return' => $return])->setPaper('a4');
    }

    public static function filename(OrderReturn $return): string
    {
        return "avoir-{$return->creditNoteNumber()}.pdf";
    }
}
