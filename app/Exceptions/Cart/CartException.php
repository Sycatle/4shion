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

    public static function emptyCart(): self
    {
        return new self('Votre panier est vide.');
    }

    public static function unavailable(string $name, int $available): self
    {
        return new self($available > 0
            ? "« {$name} » : seulement {$available} article(s) disponible(s)."
            : "« {$name} » n'est plus disponible.");
    }

    public static function invalidCoupon(string $reason = 'Ce code promo est invalide ou expiré.'): self
    {
        return new self($reason);
    }
}
