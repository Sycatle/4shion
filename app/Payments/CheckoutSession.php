<?php

declare(strict_types=1);

namespace App\Payments;

final readonly class CheckoutSession
{
    public function __construct(
        public string $id,
        public ?string $url,
        public bool $paid = false,
        public ?string $paymentIntent = null,
        public ?int $amountTotal = null,
    ) {}
}
