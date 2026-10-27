<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Address> */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'line1' => fake()->streetAddress(),
            'line2' => null,
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => 'FR',
            'phone' => fake()->phoneNumber(),
            'is_default' => false,
        ];
    }
}
