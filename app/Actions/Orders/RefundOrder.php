<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\CheckoutGateway;
use LogicException;

class RefundOrder
{
    public function __construct(private CheckoutGateway $gateway, private MarkOrderRefunded $markOrderRefunded) {}

    /**
     * Refund a paid order in full through the payment provider.
     *
     * @throws LogicException when the order has no payment to refund
     */
    public function __invoke(Order $order): void
    {
        if (! $order->status->canTransitionTo(OrderStatus::Refunded) || $order->stripe_payment_intent === null) {
            throw new LogicException("Order {$order->number} cannot be refunded.");
        }

        $this->gateway->refund($order->stripe_payment_intent);

        ($this->markOrderRefunded)($order);
    }
}
