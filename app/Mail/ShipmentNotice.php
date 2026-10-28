<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A partial parcel left: the rest of the order follows. */
final class ShipmentNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Shipment $shipment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Une partie de votre commande {$this->shipment->order->number} est en route");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.shipment-notice');
    }
}
