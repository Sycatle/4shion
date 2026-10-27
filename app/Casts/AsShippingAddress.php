<?php

declare(strict_types=1);

namespace App\Casts;

use App\Data\ShippingAddress;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a ShippingAddress as JSON and hydrates it back into the value object.
 *
 * @implements CastsAttributes<ShippingAddress, ShippingAddress|null>
 */
final class AsShippingAddress implements CastsAttributes
{
    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ShippingAddress
    {
        if (! is_string($value)) {
            return null;
        }

        $data = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

        return is_array($data) ? ShippingAddress::fromArray($data) : null;
    }

    /** @param  array<string, mixed>  $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : json_encode($value->toArray(), JSON_THROW_ON_ERROR);
    }
}
