<?php

declare(strict_types=1);

namespace App\Payments;

use App\Models\Order;

interface CheckoutGateway
{
    /** Open a hosted payment session for a pending order. */
    public function createSession(Order $order): CheckoutSession;

    public function retrieveSession(string $sessionId): CheckoutSession;

    /** Close a session so it can no longer be paid. */
    public function expireSession(string $sessionId): void;

    /**
     * Refund a payment: the whole remaining amount, or `$amount` cents for a partial refund.
     * Idempotent per `$idempotencyKey` (default: one full refund per payment intent).
     */
    public function refund(string $paymentIntent, ?int $amount = null, ?string $idempotencyKey = null): void;
}
