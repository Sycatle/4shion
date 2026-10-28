<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Data\ShippingAddress;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Payments\CheckoutGateway;
use Throwable;

final readonly class StartCheckout
{
    public function __construct(
        private CreateOrder $createOrder,
        private CancelOrder $cancelOrder,
        private CheckoutGateway $gateway,
        private MarkOrderPaid $markOrderPaid,
    ) {}

    /**
     * Create the order and return the URL of the hosted payment page.
     * If the payment provider fails, the order is cancelled and the stock released.
     * An order fully covered by gift cards needs no payment: it is marked paid straight away.
     */
    public function __invoke(User $user, ShippingAddress $address, ?ShippingMethod $shippingMethod = null): string
    {
        $order = ($this->createOrder)($user, $address, $shippingMethod);

        if ($order->amountDue() === 0) {
            ($this->markOrderPaid)($order);

            return route('account.orders.show', $order);
        }

        try {
            $session = $this->gateway->createSession($order);
        } catch (Throwable $e) {
            ($this->cancelOrder)($order, restoreCart: true);

            throw $e;
        }

        $order->update(['stripe_session_id' => $session->id]);

        return (string) $session->url;
    }
}
