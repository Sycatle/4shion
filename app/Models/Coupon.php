<?php

declare(strict_types=1);

namespace App\Models;

use App\Cart\PricedLine;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Models\Concerns\LogsActivity;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'code', 'type', 'value', 'min_subtotal', 'max_uses', 'max_uses_per_user', 'first_order_only', 'excludes_sale_items',
    'used_count', 'starts_at', 'expires_at', 'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory, LogsActivity;

    /**
     * Orders that count as a use of the coupon (or as a previous order for "first order only").
     * Pending orders are left out so that a customer retrying an abandoned payment is not locked out.
     */
    private const array COUNTED_STATUSES = [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'integer',
            'min_subtotal' => 'integer',
            'max_uses' => 'integer',
            'max_uses_per_user' => 'integer',
            'first_order_only' => 'boolean',
            'excludes_sale_items' => 'boolean',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Restricts the coupon to these categories; no category means the whole catalogue.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at?->isFuture() || $this->expires_at?->isPast()) {
            return false;
        }

        return $this->max_uses === null || $this->used_count < $this->max_uses;
    }

    /**
     * Customer-specific restrictions. A guest passes: they are checked again at checkout,
     * where the customer is always known.
     */
    public function restrictionFor(?User $user): ?string
    {
        if (! $user instanceof User) {
            return null;
        }

        $orders = $user->orders()->whereIn('status', self::COUNTED_STATUSES);

        if ($this->first_order_only && (clone $orders)->exists()) {
            return 'Ce code est réservé à une première commande.';
        }

        if ($this->max_uses_per_user !== null && (clone $orders)->where('coupon_id', $this->id)->count() >= $this->max_uses_per_user) {
            return 'Vous avez déjà utilisé ce code.';
        }

        return null;
    }

    public function isUsableBy(?User $user): bool
    {
        return $this->isUsable() && $this->restrictionFor($user) === null;
    }

    public function meetsMinimum(int $subtotal): bool
    {
        return $this->min_subtotal === null || $subtotal >= $this->min_subtotal;
    }

    /**
     * Lines the coupon applies to: never gift cards, optionally not sale items, and only the
     * restricted categories when there are some.
     *
     * @param  list<PricedLine>  $lines
     * @return list<PricedLine>
     */
    public function eligibleLines(array $lines): array
    {
        $categoryIds = $this->exists ? $this->categories->modelKeys() : [];

        return array_values(array_filter($lines, fn (PricedLine $line): bool => ! $line->isGiftCard
            && (! $this->excludes_sale_items || ! $line->isOnSale())
            && ($categoryIds === [] || in_array($line->categoryId, $categoryIds, true))));
    }

    /**
     * Discount in cents for the given subtotal, never exceeding the subtotal.
     * Free shipping coupons discount nothing here: the cart waives the shipping instead.
     */
    public function discountFor(int $subtotal): int
    {
        $discount = match ($this->type) {
            DiscountType::Percent => intdiv($subtotal * $this->value, 100),
            DiscountType::Fixed => $this->value,
            DiscountType::FreeShipping => 0,
        };

        return min($discount, $subtotal);
    }

    public function givesFreeShipping(): bool
    {
        return $this->type === DiscountType::FreeShipping;
    }
}
