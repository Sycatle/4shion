<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Mail\GiftCardsIssued;
use App\Models\GiftCard;
use Illuminate\Support\Facades\Mail;

final class SendGiftCards
{
    public function handle(OrderPaid $event): void
    {
        $giftCards = GiftCard::query()
            ->whereHas('orderItem', fn ($query) => $query->where('order_id', $event->order->id))
            ->orderBy('id')
            ->get();

        if ($giftCards->isNotEmpty()) {
            Mail::to($event->order->user)->send(new GiftCardsIssued($event->order, $giftCards));
        }
    }
}
