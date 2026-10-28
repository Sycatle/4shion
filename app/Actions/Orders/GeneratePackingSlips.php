<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

final class GeneratePackingSlips
{
    /**
     * One page per order.
     *
     * @param  iterable<Order>  $orders
     */
    public function __invoke(iterable $orders): DomPdf
    {
        $slips = [];

        foreach ($orders as $order) {
            $slips[] = $order->loadMissing('items', 'notes');
        }

        return Pdf::loadView('admin.packing-slips', ['orders' => $slips])->setPaper('a4');
    }
}
