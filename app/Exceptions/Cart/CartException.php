<?php

declare(strict_types=1);

namespace App\Exceptions\Cart;

use RuntimeException;

/**
 * Domain error whose message is safe to show to the customer.
 */
final class CartException extends RuntimeException
{
    public static function insufficientStock(int $available): self
    {
        return new self($available > 0
            ? "Stock insuffisant : seulement {$available} article(s) disponible(s)."
            : 'Cet article est actuellement épuisé.');
    }

    public static function lineLimit(int $max): self
    {
        return new self("Vous pouvez commander au maximum {$max} exemplaires d'un même article.");
    }

    public static function invalidGiftCard(): self
    {
        return new self('Cette carte cadeau est invalide, expirée ou épuisée.');
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
