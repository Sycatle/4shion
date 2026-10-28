<?php

declare(strict_types=1);

namespace App\Cart;

use App\Models\CartItem;

/**
 * A cart line reduced to what discounts need to know. Amounts in cents.
 */
final readonly class PricedLine
{
    public function __construct(
        public int $unitPrice,
        public int $regularUnitPrice,
        public int $quantity,
        public ?int $categoryId,
        public bool $isGiftCard = false,
    ) {}

    public static function fromCartItem(CartItem $item): self
    {
        $variant = $item->variant;

        return new self(
            unitPrice: $variant->price(),
            regularUnitPrice: $variant->regularPrice(),
            quantity: $item->quantity,
            categoryId: $variant->product->category_id,
            isGiftCard: (bool) $variant->product->is_gift_card,
        );
    }

    public function total(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    public function isOnSale(): bool
    {
        return $this->unitPrice < $this->regularUnitPrice;
    }
}
