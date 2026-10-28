<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\GiftCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['code', 'initial_value', 'balance', 'user_id', 'order_item_id', 'expires_at', 'is_active'])]
class GiftCard extends Model
{
    /** @use HasFactory<GiftCardFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'initial_value' => 'integer',
            'balance' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return HasMany<GiftCardRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(GiftCardRedemption::class);
    }

    public function isUsable(): bool
    {
        return $this->is_active && $this->balance > 0 && ! $this->expires_at?->isPast();
    }

    /** Codes such as GC-7KQ2-M9XA-4TPE: no ambiguous characters (0/O, 1/I/L), ~58 bits of entropy. */
    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $chars = '';

            for ($i = 0; $i < 12; $i++) {
                $chars .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $code = 'GC-'.implode('-', str_split($chars, 4));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public static function normalizeCode(string $code): string
    {
        return Str::upper(trim($code));
    }
}
