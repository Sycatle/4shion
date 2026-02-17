<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation
{
    public function handle(OrderPaid $event): void
    {
        Mail::to($event->order->user)->send(new OrderConfirmation($event->order));
    }
}
