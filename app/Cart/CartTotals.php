<?php

declare(strict_types=1);

namespace App\Cart;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\Promotion;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * Cart pricing. Sale prices are already in the line prices; on top of that, a single cart
 * discount applies: the customer's coupon or the best automatic promotion, whichever saves more.
 * A gift card then settles part or all of the total. All amounts are in cents.
 */
final readonly class CartTotals
{
    public function __construct(
        public int $itemCount,
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public int $total,
        public int $giftCard = 0,
        public int $saleSavings = 0,
        public ?Coupon $appliedCoupon = null,
        public ?Promotion $appliedPromotion = null,
        public ?string $couponNotice = null,
        public bool $giftCardsOnly = false,
        public int $freeThreshold = 0,
        public ?ShippingMethod $shippingMethod = null,
    ) {}

    /** What the customer pays by card once gift cards are deducted. */
    public function amountDue(): int
    {
        return $this->total - $this->giftCard;
    }

    public function discountLabel(): string
    {
        return match (true) {
            $this->appliedCoupon instanceof Coupon => "Code {$this->appliedCoupon->code}",
            $this->appliedPromotion instanceof Promotion => $this->appliedPromotion->name,
            default => 'Réduction',
        };
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @param  Collection<int, Promotion>  $promotions  running promotions
     */
    public static function calculate(
        Collection $items,
        ?Coupon $coupon,
        Collection $promotions = new Collection,
        ?GiftCard $giftCard = null,
        ?User $user = null,
        ?ShippingMethod $shippingMethod = null,
    ): self {
        $lines = array_values($items->map(fn (CartItem $item): PricedLine => PricedLine::fromCartItem($item))->all());

        $subtotal = array_sum(array_map(fn (PricedLine $line): int => $line->total(), $lines));
        $itemCount = array_sum(array_map(fn (PricedLine $line): int => $line->quantity, $lines));
        $saleSavings = array_sum(array_map(fn (PricedLine $line): int => ($line->regularUnitPrice - $line->unitPrice) * $line->quantity, $lines));

        $physical = array_values(array_filter($lines, fn (PricedLine $line): bool => ! $line->isGiftCard));
        $merchandise = array_sum(array_map(fn (PricedLine $line): int => $line->total(), $physical));
        $flatRate = $physical === [] ? 0 : ($shippingMethod instanceof ShippingMethod ? $shippingMethod->price : Config::integer('shop.shipping.flat_rate'));
        $freeThreshold = $shippingMethod instanceof ShippingMethod ? $shippingMethod->freeThreshold() : Config::integer('shop.shipping.free_threshold');

        [$promotion, $promotionDiscount] = self::bestPromotion($promotions, $lines);

        $couponDiscount = 0;
        $couponSavesShipping = false;
        $couponNotice = null;

        if ($coupon instanceof Coupon) {
            if (! $coupon->isUsable()) {
                $couponNotice = 'Ce code promo n\'est plus valable.';
            } elseif (($restriction = $coupon->restrictionFor($user)) !== null) {
                $couponNotice = $restriction;
            } elseif (! $coupon->meetsMinimum($merchandise)) {
                $couponNotice = 'Le montant minimum de ce code n\'est pas atteint.';
            } else {
                $eligible = array_sum(array_map(fn (PricedLine $line): int => $line->total(), $coupon->eligibleLines($lines)));
                $couponDiscount = $coupon->discountFor($eligible);
                $couponSavesShipping = $coupon->givesFreeShipping() && $flatRate > 0 && $merchandise < $freeThreshold;
            }
        }

        $couponValue = $couponDiscount + ($couponSavesShipping ? $flatRate : 0);
        $couponApplies = $coupon instanceof Coupon && $couponNotice === null && $couponValue >= $promotionDiscount && $couponValue > 0;

        if ($coupon instanceof Coupon && $couponNotice === null && ! $couponApplies) {
            $couponNotice = match (true) {
                $promotion instanceof Promotion => "La promotion « {$promotion->name} » est plus avantageuse : les remises ne se cumulent pas.",
                $coupon->givesFreeShipping() => 'La livraison est déjà offerte pour ce panier.',
                default => 'Ce code ne s\'applique à aucun article de votre panier.',
            };
        }

        $discount = $couponApplies ? $couponDiscount : $promotionDiscount;

        $shipping = match (true) {
            $flatRate === 0,
            $couponApplies && $couponSavesShipping,
            $merchandise - $discount >= $freeThreshold => 0,
            default => $flatRate,
        };

        $total = $subtotal - $discount + $shipping;
        $giftCardAmount = $giftCard instanceof GiftCard && $giftCard->isUsable() ? min($giftCard->balance, $total) : 0;

        return new self(
            itemCount: $itemCount,
            subtotal: $subtotal,
            discount: $discount,
            shipping: $shipping,
            total: $total,
            giftCard: $giftCardAmount,
            saleSavings: $saleSavings,
            appliedCoupon: $couponApplies ? $coupon : null,
            appliedPromotion: $couponApplies || $discount === 0 ? null : $promotion,
            couponNotice: $couponNotice,
            giftCardsOnly: $lines !== [] && $physical === [],
            freeThreshold: $freeThreshold,
            shippingMethod: $physical === [] ? null : $shippingMethod,
        );
    }

    /**
     * @param  Collection<int, Promotion>  $promotions
     * @param  list<PricedLine>  $lines
     * @return array{0: ?Promotion, 1: int}
     */
    private static function bestPromotion(Collection $promotions, array $lines): array
    {
        $best = null;
        $bestDiscount = 0;

        foreach ($promotions as $promotion) {
            $discount = $promotion->discountFor($lines);

            if ($discount > $bestDiscount) {
                $best = $promotion;
                $bestDiscount = $discount;
            }
        }

        return [$best, $bestDiscount];
    }
}
