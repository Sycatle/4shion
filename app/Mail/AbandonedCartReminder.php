<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AbandonedCartReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Cart $cart, public ?string $couponCode = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Vous avez oublié quelque chose dans votre panier');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.abandoned-cart');
    }
}
