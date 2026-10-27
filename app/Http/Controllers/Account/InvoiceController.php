<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Orders\GenerateInvoice;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class InvoiceController extends Controller
{
    public function __invoke(Order $order, GenerateInvoice $generateInvoice): Response
    {
        Gate::authorize('view', $order);
        abort_unless($order->status->isPaid(), 404);

        return $generateInvoice($order)->download(GenerateInvoice::filename($order));
    }
}
