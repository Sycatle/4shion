<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Carrier;
use App\Enums\DiscountType;
use App\Enums\PromotionType;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\ShippingMethod;
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

        User::factory()->create(['name' => 'Léa Préparation', 'email' => 'preparation@4shion.test', 'role' => Role::Preparer]);
        User::factory()->create(['name' => 'Sam Support', 'email' => 'support@4shion.test', 'role' => Role::Support]);

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

        Coupon::create(['code' => 'PORTOFFERT', 'type' => DiscountType::FreeShipping, 'value' => 0, 'min_subtotal' => 3000, 'excludes_sale_items' => true]);
        Coupon::create(['code' => 'PREMIEREFOIS', 'type' => DiscountType::Percent, 'value' => 15, 'first_order_only' => true, 'max_uses_per_user' => 1]);

        ShippingMethod::create(['name' => 'Colissimo à domicile', 'carrier' => Carrier::Colissimo, 'delay' => '2 à 4 jours ouvrés', 'price' => 490, 'position' => 1]);
        ShippingMethod::create(['name' => 'Chronopost Express', 'carrier' => Carrier::Chronopost, 'delay' => 'Livraison en 24 h', 'price' => 1290, 'free_threshold' => 30000, 'position' => 2]);

        Promotion::create(['name' => '-10 % dès 150 €', 'type' => PromotionType::SpendThreshold, 'min_subtotal' => 15000, 'percent' => 10]);
        Promotion::create(['name' => '3 tees achetés, le moins cher offert', 'type' => PromotionType::BuyXGetCheapestFree, 'buy_quantity' => 3, 'category_id' => Category::query()->where('slug', 't-shirts')->value('id')]);

        Category::query()->where('slug', 't-shirts')->update([
            'content' => "## Des t-shirts pensés pour durer\n\nNos t-shirts sont taillés dans un coton peigné de **220 à 280 g/m²** : une matière dense qui garde sa forme lavage après lavage. Coupes droites, oversize ou longline, il y en a pour chaque silhouette.\n\n- Col côtelé renforcé\n- Coton biologique ou peigné\n- Lavage à 30 °C",
            'meta_title' => 'T-shirts premium en coton épais',
            'meta_description' => 'T-shirts en coton peigné 220 à 280 g/m², coupes droites, oversize ou longline. Livraison offerte dès 100 €.',
        ]);

        Post::create([
            'title' => 'Pourquoi un molleton de 450 g/m²',
            'slug' => 'molleton-450',
            'excerpt' => 'Coulisses de fabrication du Hoodie Atlas : le choix d’une matière lourde.',
            'body' => "Le **Hoodie Atlas** est tissé dans un molleton gratté de 450 g/m².\n\n## Une matière qui tient\n\nUn molleton lourd garde sa tenue, ne vrille pas et se patine avec le temps.\n\n## Comment l’entretenir\n\n- Lavage à 30 °C, à l’envers\n- Séchage à plat",
            'published_at' => now()->subDays(3),
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
