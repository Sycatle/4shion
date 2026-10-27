<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

final class OrderShipped
{
    use Dispatchable;

    public function __construct(public Order $order) {}
}
