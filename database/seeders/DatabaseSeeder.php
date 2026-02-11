<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin 4shion',
            'email' => 'admin@4shion.test',
            'is_admin' => true,
        ]);

        $customer = User::factory()->create([
            'name' => 'Camille Martin',
            'email' => 'client@4shion.test',
        ]);

        $this->call(CatalogSeeder::class);

        Coupon::create(['code' => 'BIENVENUE10', 'type' => DiscountType::Percent, 'value' => 10]);
        Coupon::create([
            'code' => 'PREMIUM20',
            'type' => DiscountType::Fixed,
            'value' => 2000,
            'min_subtotal' => 10000,
        ]);
        Coupon::create([
            'code' => 'EXPIRE',
            'type' => DiscountType::Percent,
            'value' => 15,
            'expires_at' => now()->subWeek(),
        ]);

        $reviewers = User::factory()->count(5)->create()->push($customer);

        Product::query()->inRandomOrder()->limit(10)->get()->each(function (Product $product) use ($reviewers): void {
            $reviewers->random(3)->each(fn (User $user) => Review::factory()->create([
                'product_id' => $product->id,
                'user_id' => $user->id,
            ]));
        });
    }
}
