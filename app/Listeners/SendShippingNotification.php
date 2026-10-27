<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Mail\OrderShippedNotice;
use Illuminate\Support\Facades\Mail;

final class SendShippingNotification
{
    public function handle(OrderShipped $event): void
    {
        Mail::to($event->order->user)->send(new OrderShippedNotice($event->order));
    }
}
