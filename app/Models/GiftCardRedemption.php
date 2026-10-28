<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read GiftCard $giftCard
 * @property-read Order $order
 */
#[Fillable(['gift_card_id', 'order_id', 'amount', 'reversed_at'])]
class GiftCardRedemption extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'reversed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<GiftCard, $this> */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
