<?php

namespace App\Listeners;

use App\Jobs\ProcessStripeEvent;
use App\Models\StripeEvent;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Records each Stripe event once and hands it to the queue, so the webhook answers fast and
 * failures are retried by the worker. Signature verification is done by Cashier's middleware.
 */
final class HandleStripeWebhook
{
    public function handle(WebhookReceived $event): void
    {
        $id = $event->payload['id'] ?? null;

        if (! is_string($id)) {
            return;
        }

        $recorded = StripeEvent::query()->insertOrIgnore([
            'id' => $id,
            'type' => (string) ($event->payload['type'] ?? ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($recorded === 0) {
            return;
        }

        ProcessStripeEvent::dispatch($event->payload);
    }
}
