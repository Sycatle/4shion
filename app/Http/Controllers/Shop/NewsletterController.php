<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;

final class NewsletterController extends Controller
{
    public function confirm(string $token): View
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->firstOrFail();

        if ($subscriber->confirmed_at === null || $subscriber->unsubscribed_at !== null) {
            $subscriber->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);
        }

        return view('shop.newsletter-status', ['heading' => 'Inscription confirmée', 'message' => 'Merci, vous recevrez désormais nos nouveautés. Chaque e-mail contient un lien de désinscription.']);
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->firstOrFail();
        $subscriber->update(['unsubscribed_at' => $subscriber->unsubscribed_at ?? now()]);

        return view('shop.newsletter-status', ['heading' => 'Vous êtes désinscrit', 'message' => 'Votre adresse ne recevra plus la newsletter.']);
    }
}
