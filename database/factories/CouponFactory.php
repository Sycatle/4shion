<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('????')).fake()->numberBetween(10, 99),
            'type' => DiscountType::Percent,
            'value' => 10,
            'min_subtotal' => null,
            'max_uses' => null,
            'used_count' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function fixed(int $cents): static
    {
        return $this->state(['type' => DiscountType::Fixed, 'value' => $cents]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
