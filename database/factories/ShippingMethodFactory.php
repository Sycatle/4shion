<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Carrier;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShippingMethod> */
class ShippingMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Colissimo à domicile',
            'carrier' => Carrier::Colissimo,
            'delay' => '2 à 4 jours ouvrés',
            'price' => 490,
            'free_threshold' => null,
            'is_active' => true,
            'position' => 0,
        ];
    }
}
