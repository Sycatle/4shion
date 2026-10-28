<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class NewLoginNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $ip, public string $userAgent) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nouvelle connexion à votre compte');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-login');
    }
}
