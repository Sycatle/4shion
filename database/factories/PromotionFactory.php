<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PromotionType;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Promotion> */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => '-10 % dès 150 €',
            'type' => PromotionType::SpendThreshold,
            'category_id' => null,
            'min_subtotal' => 15000,
            'percent' => 10,
            'buy_quantity' => null,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    public function cheapestFree(int $buyQuantity = 3): static
    {
        return $this->state([
            'name' => "{$buyQuantity} achetés, le moins cher offert",
            'type' => PromotionType::BuyXGetCheapestFree,
            'min_subtotal' => null,
            'percent' => null,
            'buy_quantity' => $buyQuantity,
        ]);
    }
}
