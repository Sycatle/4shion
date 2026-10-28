<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * A customer's request to send back part of an order.
 *
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read Order $order
 */
#[Fillable(['order_id', 'number', 'status', 'reason', 'comment', 'decision_note', 'refund_amount', 'received_at', 'refunded_at'])]
class OrderReturn extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => ReturnStatus::class,
            'reason' => ReturnReason::class,
            'refund_amount' => 'integer',
            'received_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<OrderReturnItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    /**
     * @param  array<string, mixed>  $attributes  extra columns to persist with the transition
     *
     * @throws InvalidArgumentException when the lifecycle does not allow the move
     */
    public function transitionTo(ReturnStatus $target, array $attributes = []): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidArgumentException("Le retour {$this->number} ne peut pas passer à « {$target->label()} ».");
        }

        $this->update(['status' => $target, ...$attributes]);
    }

    public static function numberFor(int $id): string
    {
        return sprintf('RET-%s-%06d', now()->format('Y'), $id);
    }

    /** Credit note number, issued once the refund is paid. */
    public function creditNoteNumber(): string
    {
        return 'AV-'.substr($this->number, 4);
    }

    /** Value of the returned goods in cents, after the order-level discount share. */
    public function goodsValue(): int
    {
        $order = $this->order;
        $items = $this->items->loadMissing('orderItem');
        $gross = $items->sum(fn (OrderReturnItem $line): int => $line->orderItem->unit_price * $line->quantity);

        if ($order->subtotal === 0 || $order->discount_total === 0) {
            return (int) $gross;
        }

        return (int) round($gross * ($order->subtotal - $order->discount_total) / $order->subtotal);
    }
}
