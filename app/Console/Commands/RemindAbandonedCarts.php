<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\AbandonedCartReminder;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/** One reminder per cart, only for signed-in customers who left items and have not ordered since. */
#[Signature('carts:remind-abandoned')]
#[Description('E-mail customers who left items in their cart')]
final class RemindAbandonedCarts extends Command
{
    public function handle(): int
    {
        $idleSince = now()->subHours(Config::integer('shop.abandoned_cart.after_hours'));
        $activeSince = now()->subDays(Config::integer('shop.abandoned_cart.max_days'));
        $coupon = Config::get('shop.abandoned_cart.coupon');
        $sent = 0;

        Cart::query()
            ->whereNotNull('user_id')
            ->whereNull('reminder_sent_at')
            ->whereHas('items', fn (Builder $items) => $items->where('updated_at', '>=', $activeSince))
            ->whereDoesntHave('items', fn (Builder $items) => $items->where('updated_at', '>', $idleSince))
            ->whereDoesntHave('user.orders', fn (Builder $orders) => $orders->where('created_at', '>=', $activeSince))
            ->with(['user', 'items.variant.product'])
            ->lazyById()
            ->each(function (Cart $cart) use ($coupon, &$sent): void {
                if (! $cart->user instanceof User) {
                    return;
                }

                Mail::to($cart->user)->send(new AbandonedCartReminder($cart, is_string($coupon) ? $coupon : null));
                $cart->forceFill(['reminder_sent_at' => now()])->save();
                $sent++;
            });

        $this->components->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
