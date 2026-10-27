<?php

namespace App\Models;

use App\Data\ShippingAddress;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Non-nullable foreign keys: these relations are always present.
 *
 * @property-read User $user
 */
#[Fillable(['user_id', 'first_name', 'last_name', 'line1', 'line2', 'postal_code', 'city', 'country', 'phone', 'is_default'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function toShippingAddress(): ShippingAddress
    {
        return new ShippingAddress(
            firstName: $this->first_name,
            lastName: $this->last_name,
            line1: $this->line1,
            line2: $this->line2,
            postalCode: $this->postal_code,
            city: $this->city,
            country: $this->country,
            phone: $this->phone,
        );
    }
}
