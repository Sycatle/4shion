<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Orders\CancelOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * Safety net for reserved stock: the `checkout.session.expired` webhook normally cancels
 * abandoned orders, but a lost delivery or a failed session creation must not lock stock forever.
 */
#[Signature('orders:release-stale {--minutes= : Age after which a pending order is considered abandoned}')]
#[Description('Cancel abandoned pending orders and release their reserved stock')]
final class ReleaseStaleOrders extends Command
{
    public function handle(CancelOrder $cancelOrder): int
    {
        $option = $this->option('minutes');
        $minutes = is_numeric($option) ? (int) $option : Config::integer('shop.pending_order_ttl_minutes');
        $released = 0;

        Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->lazyById()
            ->each(function (Order $order) use ($cancelOrder, &$released): void {
                $cancelOrder($order);
                $released++;
            });

        $this->components->info("Released {$released} stale order(s).");

        return self::SUCCESS;
    }
}
