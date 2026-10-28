<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\CheckoutGateway;
use LogicException;

final readonly class RefundOrder
{
    public function __construct(private CheckoutGateway $gateway, private MarkOrderRefunded $markOrderRefunded) {}

    /**
     * Refund a paid order in full: the card payment through the provider, the gift card part
     * back onto the card it came from. An order settled entirely by gift card has no payment.
     *
     * @throws LogicException when the order has no payment to refund
     */
    public function __invoke(Order $order): void
    {
        $paidByCard = $order->amountDue() > 0;

        if (! $order->status->canTransitionTo(OrderStatus::Refunded) || $order->refunded_total > 0 || ($paidByCard && $order->stripe_payment_intent === null)) {
            throw new LogicException("Order {$order->number} cannot be refunded.");
        }

        if ($paidByCard) {
            $this->gateway->refund($order->stripe_payment_intent);
        }

        ($this->markOrderRefunded)($order);
    }
}
