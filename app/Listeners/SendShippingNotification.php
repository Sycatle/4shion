<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Mail\OrderShippedNotice;
use Illuminate\Support\Facades\Mail;

class SendShippingNotification
{
    public function handle(OrderShipped $event): void
    {
        Mail::to($event->order->user)->send(new OrderShippedNotice($event->order));
    }
}
