<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Carrier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read Order $order
 */
#[Fillable(['order_id', 'carrier', 'tracking_number', 'shipped_at'])]
class Shipment extends Model
{
    protected function casts(): array
    {
        return [
            'carrier' => Carrier::class,
            'shipped_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<ShipmentItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function trackingUrl(): ?string
    {
        return $this->tracking_number !== null ? $this->carrier?->trackingUrl($this->tracking_number) : null;
    }
}
