<?php

declare(strict_types=1);

namespace App\Models;

use App\Cart\PricedLine;
use App\Enums\PromotionType;
use App\Models\Concerns\LogsActivity;
use App\Support\Money;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Automatic cart discount, applied without a code. Only the most favourable promotion
 * applies, and only when it beats the customer's coupon.
 */
#[Fillable(['name', 'type', 'category_id', 'min_subtotal', 'percent', 'buy_quantity', 'starts_at', 'ends_at', 'is_active'])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'type' => PromotionType::class,
            'min_subtotal' => 'integer',
            'percent' => 'integer',
            'buy_quantity' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @param  Builder<Promotion>  $query
     * @return Builder<Promotion>
     */
    #[Scope]
    protected function running(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /**
     * Discount in cents on the given lines. Gift cards and lines outside the category are ignored.
     *
     * @param  list<PricedLine>  $lines
     */
    public function discountFor(array $lines): int
    {
        $eligible = array_values(array_filter(
            $lines,
            fn (PricedLine $line): bool => ! $line->isGiftCard && ($this->category_id === null || $line->categoryId === $this->category_id),
        ));

        return match ($this->type) {
            PromotionType::SpendThreshold => $this->spendThresholdDiscount($eligible),
            PromotionType::BuyXGetCheapestFree => $this->cheapestFreeDiscount($eligible),
        };
    }

    /** Short customer-facing description, e.g. "-10 % dès 150,00 €". */
    public function summary(): string
    {
        return match ($this->type) {
            PromotionType::SpendThreshold => sprintf('-%d %% dès %s', (int) $this->percent, Money::format((int) $this->min_subtotal)),
            PromotionType::BuyXGetCheapestFree => sprintf('%d achetés, le moins cher offert', (int) $this->buy_quantity),
        };
    }

    /** @param  list<PricedLine>  $lines */
    private function spendThresholdDiscount(array $lines): int
    {
        $subtotal = array_sum(array_map(fn (PricedLine $line): int => $line->total(), $lines));

        if ($this->percent === null || $subtotal === 0 || $subtotal < (int) $this->min_subtotal) {
            return 0;
        }

        return intdiv($subtotal * $this->percent, 100);
    }

    /**
     * Units are sorted from the most to the least expensive; every Xth unit is free,
     * so the customer always gets the cheapest unit of each group.
     *
     * @param  list<PricedLine>  $lines
     */
    private function cheapestFreeDiscount(array $lines): int
    {
        if ($this->buy_quantity === null || $this->buy_quantity < 2) {
            return 0;
        }

        $units = [];

        foreach ($lines as $line) {
            array_push($units, ...array_fill(0, $line->quantity, $line->unitPrice));
        }

        rsort($units);

        $discount = 0;

        for ($i = $this->buy_quantity - 1; $i < count($units); $i += $this->buy_quantity) {
            $discount += $units[$i];
        }

        return $discount;
    }
}
