<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\ShippingAddress;
use App\Enums\OrderStatus;
use App\Exceptions\Orders\InvalidOrderTransition;
use App\Models\Concerns\LogsActivity;
use Carbon\CarbonInterface;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;

/**
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read User $user
 * @property ShippingAddress $shipping_address
 * @property ShippingAddress $billing_address
 */
#[Fillable([
    'number', 'user_id', 'status', 'subtotal', 'discount_total', 'shipping_total', 'total', 'gift_card_total', 'vat_rate', 'vat_total', 'currency',
    'coupon_id', 'coupon_code', 'promotion_id', 'promotion_name', 'shipping_address', 'billing_address',
    'stripe_session_id', 'stripe_payment_intent', 'paid_at', 'shipped_at', 'delivered_at',
    'shipping_method_id', 'shipping_method_name', 'refunded_total',
])]
#[RouteKey('number')]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'shipping_total' => 'integer',
            'total' => 'integer',
            'gift_card_total' => 'integer',
            'vat_rate' => 'decimal:2',
            'vat_total' => 'integer',
            'shipping_address' => ShippingAddress::class,
            'billing_address' => ShippingAddress::class,
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'refunded_total' => 'integer',
        ];
    }

    /** @return HasMany<Shipment, $this> */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class)->oldest('shipped_at');
    }

    /** @return HasMany<OrderReturn, $this> */
    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class)->latest();
    }

    /**
     * Whether the customer may still ask to send items back: parcel received (or at least
     * shipped) less than the configured number of days ago.
     */
    public function isReturnable(): bool
    {
        $since = $this->delivered_at ?? $this->shipped_at;

        return in_array($this->status, [OrderStatus::Shipped, OrderStatus::Delivered], true)
            && $since !== null
            && $since->copy()->addDays(Config::integer('shop.returns.window_days'))->isFuture();
    }

    /** Last day on which a return can be requested, if the order is returnable. */
    public function returnDeadline(): ?CarbonInterface
    {
        $since = $this->delivered_at ?? $this->shipped_at;

        return $since?->copy()->addDays(Config::integer('shop.returns.window_days'));
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

    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** @return HasMany<OrderNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->latest();
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<GiftCardRedemption, $this> */
    public function giftCardRedemptions(): HasMany
    {
        return $this->hasMany(GiftCardRedemption::class);
    }

    /** Amount charged through the payment provider, in cents: what gift cards did not cover. */
    public function amountDue(): int
    {
        return $this->total - $this->gift_card_total;
    }

    /** Label of the cart discount, e.g. "Code BIENVENUE10" or the promotion name. */
    public function discountLabel(): string
    {
        return match (true) {
            $this->coupon_code !== null => "Code {$this->coupon_code}",
            $this->promotion_name !== null => $this->promotion_name,
            default => 'Réduction',
        };
    }

    public function hasBackorders(): bool
    {
        return $this->items->contains(fn (OrderItem $item): bool => $item->backordered_quantity > 0);
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
