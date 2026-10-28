<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Carrier;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

#[Fillable(['name', 'carrier', 'delay', 'price', 'free_threshold', 'is_active', 'position'])]
class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'carrier' => Carrier::class,
            'price' => 'integer',
            'free_threshold' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @param  Builder<ShippingMethod>  $query
     * @return Builder<ShippingMethod>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    /** The method used until the customer picks one: the first active one, if any. */
    public static function default(): ?self
    {
        return self::query()->active()->first();
    }

    /** Spend above which shipping is free, in cents (the shop-wide value unless the method sets its own). */
    public function freeThreshold(): int
    {
        return $this->free_threshold ?? Config::integer('shop.shipping.free_threshold');
    }
}
