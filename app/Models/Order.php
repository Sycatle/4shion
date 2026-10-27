<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Exceptions\Orders\InvalidOrderTransition;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number', 'user_id', 'status', 'subtotal', 'discount_total', 'shipping_total', 'total', 'vat_rate', 'vat_total', 'currency',
    'coupon_id', 'coupon_code', 'shipping_address', 'billing_address',
    'stripe_session_id', 'stripe_payment_intent', 'paid_at', 'shipped_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'shipping_total' => 'integer',
            'total' => 'integer',
            'vat_rate' => 'decimal:2',
            'vat_total' => 'integer',
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Move the order to another status, enforcing the lifecycle defined by OrderStatus.
     * Callers that must stay idempotent check canTransitionTo() first.
     *
     * @param  array<string, mixed>  $attributes  extra columns to persist with the transition
     *
     * @throws InvalidOrderTransition
     */
    public function transitionTo(OrderStatus $target, array $attributes = []): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidOrderTransition::for($this, $target);
        }

        $this->update(['status' => $target, ...$attributes]);
    }

    /**
     * VAT contained in a tax-inclusive amount, in cents.
     */
    public static function vatIncludedIn(int $total, float $rate): int
    {
        return $total - (int) round($total / (1 + $rate / 100));
    }

    /**
     * Build a human-readable, sortable order number, e.g. 4SH-2026-000042.
     */
    public static function numberFor(int $id): string
    {
        return sprintf('4SH-%s-%06d', now()->format('Y'), $id);
    }
}
