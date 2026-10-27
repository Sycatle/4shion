<?php

declare(strict_types=1);

namespace App\Mail;

use App\Actions\Orders\GenerateInvoice;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Confirmation de votre commande {$this->order->number}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-confirmation');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => (new GenerateInvoice)($this->order)->output(),
                GenerateInvoice::filename($this->order),
            )->withMime('application/pdf'),
        ];
    }
}
