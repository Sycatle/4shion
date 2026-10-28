<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(3), '.');

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'base_price' => fake()->numberBetween(2900, 18900),
            'is_active' => true,
            'is_featured' => false,
            'is_gift_card' => false,
        ];
    }

    public function onSale(int $percent = 20): static
    {
        return $this->state(['sale_percent' => $percent]);
    }

    public function giftCard(): static
    {
        return $this->state(['is_gift_card' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
