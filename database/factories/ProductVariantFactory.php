<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('4SH-????-####')),
            'size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            'color' => fake()->randomElement(['Noir', 'Écru', 'Anthracite']),
            'color_hex' => null,
            'price_override' => null,
            'stock' => fake()->numberBetween(1, 25),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
