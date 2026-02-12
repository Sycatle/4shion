<?php

namespace Tests\Support;

use App\Models\Order;
use App\Payments\CheckoutGateway;
use App\Payments\CheckoutSession;
use RuntimeException;

class FakeCheckoutGateway implements CheckoutGateway
{
    public bool $fails = false;

    public bool $sessionPaid = false;

    /** @var list<string> */
    public array $expired = [];

    public function createSession(Order $order): CheckoutSession
    {
        if ($this->fails) {
            throw new RuntimeException('Stripe is down.');
        }

        return new CheckoutSession("cs_test_{$order->id}", "https://checkout.test/pay/{$order->id}");
    }

    public function retrieveSession(string $sessionId): CheckoutSession
    {
        $order = Order::where('stripe_session_id', $sessionId)->firstOrFail();

        return new CheckoutSession($sessionId, null, $this->sessionPaid, 'pi_test_123', $order->total);
    }

    public function expireSession(string $sessionId): void
    {
        $this->expired[] = $sessionId;
    }
}
