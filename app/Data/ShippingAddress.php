<?php

namespace App\Data;

final readonly class ShippingAddress
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
