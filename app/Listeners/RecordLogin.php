<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Mail\NewLoginNotice;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** Remembers where the last sign-in came from and warns the owner when it comes from somewhere new. */
final readonly class RecordLogin
{
    public function __construct(private Request $request) {}

    public function handle(Login $event): void
    {
        $user = $event->user;
        $ip = $this->request->ip();

        if (! $user instanceof User || $ip === null) {
            return;
        }

        $previous = $user->last_login_ip;

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $ip])->saveQuietly();

        if (is_string($previous) && $previous !== $ip) {
            Mail::to($user)->send(new NewLoginNotice($user, $ip, (string) $this->request->userAgent()));
        }
    }
}
