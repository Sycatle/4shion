<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\StockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class BackInStockNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public StockAlert $alert) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->alert->variant->product->name} est de retour en stock");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.back-in-stock');
    }
}
