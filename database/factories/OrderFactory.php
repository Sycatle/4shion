<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    private const VAT_RATE = 20.0;

    public function definition(): array
    {
        $address = Address::factory()->make()->toShippingAddress();
        $subtotal = fake()->numberBetween(4900, 30000);
        $total = $subtotal + 490;

        return [
            'number' => fn () => 'TMP-'.fake()->unique()->numerify('########'),
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'shipping_total' => 490,
            'total' => $total,
            'vat_rate' => self::VAT_RATE,
            'vat_total' => Order::vatIncludedIn($total, self::VAT_RATE),
            'currency' => 'EUR',
            'shipping_address' => $address,
            'billing_address' => $address,
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /** Free-shipping, undiscounted order of the given amount (in cents). */
    public function totalling(int $cents): static
    {
        return $this->state([
            'subtotal' => $cents,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => $cents,
            'vat_total' => Order::vatIncludedIn($cents, self::VAT_RATE),
        ]);
    }
}
