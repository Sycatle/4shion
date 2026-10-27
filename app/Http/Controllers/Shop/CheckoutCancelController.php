<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\Orders\CancelOrder;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CheckoutCancelController extends Controller
{
    /**
     * The customer left the payment page: release the reserved stock and give the cart back.
     */
    public function __invoke(Request $request, Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()?->id, 404);

        $cancelOrder($order, restoreCart: true);

        return to_route('cart')->with('status', 'Paiement annulé : votre panier a été restauré.');
    }
}
