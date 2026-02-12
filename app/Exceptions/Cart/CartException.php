<?php

namespace App\Exceptions\Cart;

use RuntimeException;

/**
 * Domain error whose message is safe to show to the customer.
 */
class CartException extends RuntimeException
{
    public static function insufficientStock(int $available): self
    {
        return new self($available > 0
            ? "Stock insuffisant : seulement {$available} article(s) disponible(s)."
            : 'Cet article est actuellement épuisé.');
    }

    public static function invalidCoupon(string $reason = 'Ce code promo est invalide ou expiré.'): self
    {
        return new self($reason);
    }
}
