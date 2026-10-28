<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\ReturnStatus;
use App\Models\OrderReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ReturnUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public OrderReturn $return) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->return->status) {
            ReturnStatus::Requested => 'Nous avons bien reçu votre demande de retour',
            ReturnStatus::Approved => 'Votre retour est accepté',
            ReturnStatus::Rejected => 'Votre demande de retour',
            ReturnStatus::Received => 'Nous avons reçu votre colis',
            ReturnStatus::Refunded => 'Votre remboursement est parti',
        };

        return new Envelope(subject: "{$subject} ({$this->return->number})");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.return-update');
    }
}
