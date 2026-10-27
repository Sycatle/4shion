<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Actions\Orders\CancelOrder;
use App\Data\ShippingAddress;
use App\Models\User;
use App\Payments\CheckoutGateway;
use Throwable;

final readonly class StartCheckout
{
    public function __construct(
        private CreateOrder $createOrder,
        private CancelOrder $cancelOrder,
        private CheckoutGateway $gateway,
    ) {}

    /**
     * Create the order and return the URL of the hosted payment page.
     * If the payment provider fails, the order is cancelled and the stock released.
     */
    public function __invoke(User $user, ShippingAddress $address): string
    {
        $order = ($this->createOrder)($user, $address);

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
