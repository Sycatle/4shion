<?php

declare(strict_types=1);

namespace App\Data;

use App\Casts\AsShippingAddress;
use Illuminate\Contracts\Database\Eloquent\Castable;

final readonly class ShippingAddress implements Castable
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $line1,
        public ?string $line2,
        public string $postalCode,
        public string $city,
        public string $country = 'FR',
        public ?string $phone = null,
    ) {}

    /** @param  array<mixed>  $data */
    public static function fromArray(array $data): self
    {
        $string = fn (string $key): string => is_scalar($data[$key] ?? null) ? (string) $data[$key] : '';
        $nullable = fn (string $key): ?string => is_scalar($data[$key] ?? null) && $data[$key] !== '' ? (string) $data[$key] : null;

        return new self(
            firstName: $string('first_name'),
            lastName: $string('last_name'),
            line1: $string('line1'),
            line2: $nullable('line2'),
            postalCode: $string('postal_code'),
            city: $string('city'),
            country: $nullable('country') ?? 'FR',
            phone: $nullable('phone'),
        );
    }

    /**
     * @param  array<mixed>  $arguments
     * @return class-string<AsShippingAddress>
     */
    public static function castUsing(array $arguments): string
    {
        return AsShippingAddress::class;
    }

    public function fullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
            'phone' => $this->phone,
        ];
    }
}
