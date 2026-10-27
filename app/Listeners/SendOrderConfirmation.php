<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Mail;

final class SendOrderConfirmation
{
    public function handle(OrderPaid $event): void
    {
        Mail::to($event->order->user)->send(new OrderConfirmation($event->order));
    }
}
