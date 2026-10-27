<?php

declare(strict_types=1);

namespace App\Exceptions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use LogicException;

final class InvalidOrderTransition extends LogicException
{
    public static function for(Order $order, OrderStatus $target): self
    {
        return new self(sprintf(
            'Order %s cannot move from "%s" to "%s".',
            $order->number,
            $order->status->value,
            $target->value,
        ));
    }
}
