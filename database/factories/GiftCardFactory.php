<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GiftCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GiftCard> */
class GiftCardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => GiftCard::generateCode(),
            'initial_value' => 5000,
            'balance' => 5000,
            'user_id' => null,
            'order_item_id' => null,
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ];
    }

    public function worth(int $cents): static
    {
        return $this->state(['initial_value' => $cents, 'balance' => $cents]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
