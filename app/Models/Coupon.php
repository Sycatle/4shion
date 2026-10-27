<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'type', 'value', 'min_subtotal', 'max_uses', 'used_count', 'starts_at', 'expires_at', 'is_active'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'integer',
            'min_subtotal' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
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

    public function meetsMinimum(int $subtotal): bool
    {
        return $this->min_subtotal === null || $subtotal >= $this->min_subtotal;
    }

    /**
     * Discount in cents for the given subtotal, never exceeding the subtotal.
     */
    public function discountFor(int $subtotal): int
    {
        $discount = match ($this->type) {
            DiscountType::Percent => intdiv($subtotal * $this->value, 100),
            DiscountType::Fixed => $this->value,
        };

        return min($discount, $subtotal);
    }
}
