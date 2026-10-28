<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\GiftCard;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

final class GiftCardsIssued extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param  Collection<int, GiftCard>  $giftCards */
    public function __construct(public Order $order, public Collection $giftCards) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Vos cartes cadeaux '.Config::string('app.name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.gift-cards');
    }
}
