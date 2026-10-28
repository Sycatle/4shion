<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

/** Double opt-in: nothing is sent until the address owner clicks the confirmation link. */
final class NewsletterForm extends Component
{
    private const int MAX_ATTEMPTS_PER_MINUTE = 5;

    public string $email = '';

    public bool $submitted = false;

    public ?string $error = null;

    public function subscribe(): void
    {
        $this->error = null;
        $this->email = Str::lower(trim($this->email));
        $this->validate(['email' => ['required', 'email:rfc', 'max:255']], attributes: ['email' => 'e-mail']);

        $key = 'newsletter:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS_PER_MINUTE)) {
            $this->error = 'Trop de tentatives. Réessayez dans une minute.';

            return;
        }

        RateLimiter::hit($key);

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $this->email]);

        // Same answer whether or not the address is already known: no way to probe the list.
        if (! $subscriber->isActive()) {
            $subscriber->fill(['token' => NewsletterSubscriber::newToken(), 'confirmed_at' => null, 'unsubscribed_at' => null])->save();
            Mail::to($subscriber->email)->send(new NewsletterConfirmation($subscriber));
        }

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.shop.newsletter-form');
    }
}
