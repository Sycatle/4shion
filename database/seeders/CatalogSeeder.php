<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    private const array CLOTHING_SIZES = ['XS', 'S', 'M', 'L', 'XL'];

    private const array COLORS = [
        'Noir' => '#111111',
        'Écru' => '#E9E4D8',
        'Anthracite' => '#3A3D40',
        'Sable' => '#C8B79A',
        'Olive' => '#5B6147',
        'Bleu nuit' => '#1B2438',
    ];

    public function run(): void
    {
        foreach ($this->catalog() as $position => $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
                'position' => $position,
            ]);

            foreach ($categoryData['products'] as $productData) {
                $this->createProduct($category, $productData, $categoryData['sizes'] ?? self::CLOTHING_SIZES);
            }
        }
    }

    /**
     * @param  array{name: string, description: string, price: int, colors: list<string>, featured?: bool}  $data
     * @param  list<string>  $sizes
     */
    private function createProduct(Category $category, array $data, array $sizes): void
    {
        $product = Product::create([
            'category_id' => $category->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'],
            'specs' => $this->specs()[$data['name']] ?? null,
            'base_price' => $data['price'],
            'is_active' => true,
            'is_featured' => $data['featured'] ?? false,
        ]);

        foreach ($data['colors'] as $color) {
            foreach ($sizes as $size) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => sprintf('4SH-%04d-%s-%s', $product->id, Str::upper(Str::substr(Str::slug($color), 0, 3)), $size),
                    'size' => $size,
                    'color' => $color,
                    'color_hex' => self::COLORS[$color],
                    'stock' => random_int(0, 4) === 0 ? 0 : random_int(3, 30),
                ]);
            }
        }

        $this->attachImage($product);
    }

    private function attachImage(Product $product): void
    {
        foreach (['webp', 'png', 'jpg'] as $extension) {
            $path = database_path("seeders/images/products/{$product->slug}.{$extension}");

            if (is_file($path)) {
                $product->addMedia($path)->preservingOriginal()->toMediaCollection(Product::IMAGES);

                return;
            }
        }
    }

    /**
     * @return array<string, array{composition: string, weight: string, fit: string, care: string}>
     */
    private function specs(): array
    {
        $wash = 'Lavage machine 30 °C, à l\'envers. Séchage à plat.';

        return [
            'Hoodie Atlas' => ['composition' => '100 % coton biologique', 'weight' => '450 g/m²', 'fit' => 'Oversize', 'care' => $wash],
            'Sweat Meridian' => ['composition' => '100 % coton biologique brossé', 'weight' => '380 g/m²', 'fit' => 'Droite', 'care' => $wash],
            'Hoodie Zip Orion' => ['composition' => '85 % coton, 15 % polyester recyclé', 'weight' => '400 g/m²', 'fit' => 'Regular', 'care' => $wash],
            'Sweat Quartz' => ['composition' => '100 % coton', 'weight' => '360 g/m²', 'fit' => 'Boxy', 'care' => $wash],
            'Tee Essential' => ['composition' => '100 % coton peigné', 'weight' => '220 g/m²', 'fit' => 'Droite', 'care' => $wash],
            'Tee Oversize Nova' => ['composition' => '100 % coton', 'weight' => '250 g/m²', 'fit' => 'Oversize', 'care' => $wash],
            'Tee Heavy Apex' => ['composition' => '100 % coton', 'weight' => '280 g/m²', 'fit' => 'Boxy', 'care' => $wash],
            'Polo Lumen' => ['composition' => '100 % coton piqué', 'weight' => '230 g/m²', 'fit' => 'Regular', 'care' => $wash],
            'Tee Longline Vega' => ['composition' => '100 % coton jersey', 'weight' => '190 g/m²', 'fit' => 'Allongée', 'care' => $wash],
            'Veste Overshirt Kodo' => ['composition' => '100 % coton twill', 'weight' => '320 g/m²', 'fit' => 'Ample', 'care' => 'Lavage machine 30 °C. Repassage doux.'],
            'Bomber Helix' => ['composition' => '100 % nylon, doublure polyester', 'weight' => '210 g/m²', 'fit' => 'Regular', 'care' => 'Nettoyage à la main, séchage à plat.'],
            'Manteau Long Altus' => ['composition' => '70 % laine, 30 % polyamide', 'weight' => '520 g/m²', 'fit' => 'Droite', 'care' => 'Nettoyage à sec uniquement.'],
            'Coupe-vent Strata' => ['composition' => '100 % nylon ripstop déperlant', 'weight' => '90 g/m²', 'fit' => 'Regular', 'care' => 'Lavage machine 30 °C, sans assouplissant.'],
            'Cargo Vector' => ['composition' => '100 % coton ripstop', 'weight' => '240 g/m²', 'fit' => 'Droite', 'care' => $wash],
            'Pantalon Tailored Onyx' => ['composition' => '100 % laine froide', 'weight' => '260 g/m²', 'fit' => 'Fuselée', 'care' => 'Nettoyage à sec uniquement.'],
            'Jogger Pulse' => ['composition' => '90 % coton, 10 % polyester', 'weight' => '300 g/m²', 'fit' => 'Ajustée aux chevilles', 'care' => $wash],
            'Short Drift' => ['composition' => '100 % coton', 'weight' => '320 g/m²', 'fit' => 'Mi-cuisse', 'care' => $wash],
            'Casquette Signal' => ['composition' => '100 % coton twill', 'weight' => '—', 'fit' => 'Réglable', 'care' => 'Nettoyage à la main.'],
            'Bonnet Fjord' => ['composition' => '100 % laine mérinos', 'weight' => '—', 'fit' => 'Taille unique', 'care' => 'Lavage à la main à 20 °C.'],
            'Tote Bag Canvas' => ['composition' => '100 % coton, toile 12 oz', 'weight' => '340 g/m²', 'fit' => '38 × 42 cm', 'care' => 'Lavage machine 30 °C.'],
        ];
    }

    /**
     * @return list<array{
     *     name: string,
     *     description: string,
     *     sizes?: list<string>,
     *     products: list<array{name: string, description: string, price: int, colors: list<string>, featured?: bool}>,
     * }>
     */
    private function catalog(): array
    {
        return [
            [
                'name' => 'Sweats & Hoodies',
                'description' => 'Molleton épais, coupes oversize et finitions soignées.',
                'products' => [
                    ['name' => 'Hoodie Atlas', 'description' => 'Hoodie oversize en molleton gratté 450 g/m², capuche doublée et poche kangourou.', 'price' => 8900, 'colors' => ['Noir', 'Écru', 'Anthracite'], 'featured' => true],
                    ['name' => 'Sweat Meridian', 'description' => 'Sweat col rond à coupe droite, coton biologique brossé.', 'price' => 7500, 'colors' => ['Sable', 'Noir']],
                    ['name' => 'Hoodie Zip Orion', 'description' => 'Hoodie zippé en jersey lourd, zip métal brossé et finitions tonales.', 'price' => 9500, 'colors' => ['Anthracite', 'Olive']],
                    ['name' => 'Sweat Quartz', 'description' => 'Sweat demi-zip minimaliste, col montant et coupe boxy.', 'price' => 8200, 'colors' => ['Écru', 'Bleu nuit']],
                ],
            ],
            [
                'name' => 'T-shirts',
                'description' => 'Basiques premium en coton épais, coupe structurée.',
                'products' => [
                    ['name' => 'Tee Essential', 'description' => 'T-shirt en coton peigné 220 g/m², col côtelé renforcé et coupe droite.', 'price' => 3500, 'colors' => ['Noir', 'Écru', 'Olive'], 'featured' => true],
                    ['name' => 'Tee Oversize Nova', 'description' => 'T-shirt oversize aux épaules tombantes, tombé fluide et matière dense.', 'price' => 4200, 'colors' => ['Noir', 'Sable']],
                    ['name' => 'Tee Heavy Apex', 'description' => 'T-shirt heavyweight 280 g/m², finitions piquées et lavage enzymatique.', 'price' => 4500, 'colors' => ['Anthracite', 'Écru']],
                    ['name' => 'Polo Lumen', 'description' => 'Polo en maille piquée, col fin et boutonnage nacré.', 'price' => 5900, 'colors' => ['Bleu nuit', 'Écru']],
                    ['name' => 'Tee Longline Vega', 'description' => 'T-shirt allongé à ourlet arrondi, coton jersey souple.', 'price' => 3900, 'colors' => ['Noir', 'Sable']],
                ],
            ],
            [
                'name' => 'Vestes & Manteaux',
                'description' => 'Pièces de saison pensées pour durer.',
                'products' => [
                    ['name' => 'Veste Overshirt Kodo', 'description' => 'Surchemise en twill épais, quatre poches plaquées et coupe ample.', 'price' => 12900, 'colors' => ['Olive', 'Noir'], 'featured' => true],
                    ['name' => 'Bomber Helix', 'description' => 'Bomber en nylon satiné, doublure matelassée et bords-côtes tonals.', 'price' => 14900, 'colors' => ['Noir', 'Bleu nuit']],
                    ['name' => 'Manteau Long Altus', 'description' => 'Manteau long en laine mélangée, fermeture cachée et coupe droite.', 'price' => 19900, 'colors' => ['Anthracite', 'Sable']],
                    ['name' => 'Coupe-vent Strata', 'description' => 'Coupe-vent déperlant ultra-léger, capuche réglable, repliable dans sa poche.', 'price' => 11900, 'colors' => ['Noir', 'Olive']],
                ],
            ],
            [
                'name' => 'Pantalons',
                'description' => 'Coupes modernes, matières techniques et confortables.',
                'products' => [
                    ['name' => 'Cargo Vector', 'description' => 'Pantalon cargo droit en coton ripstop, poches latérales à soufflets.', 'price' => 9900, 'colors' => ['Olive', 'Noir']],
                    ['name' => 'Pantalon Tailored Onyx', 'description' => 'Pantalon à pinces en laine froide, taille haute et jambe fuselée.', 'price' => 11900, 'colors' => ['Noir', 'Anthracite']],
                    ['name' => 'Jogger Pulse', 'description' => 'Jogger en molleton léger, taille élastique et chevilles ajustées.', 'price' => 6900, 'colors' => ['Anthracite', 'Sable', 'Noir']],
                    ['name' => 'Short Drift', 'description' => 'Short en coton lourd, coupe mi-cuisse et cordon de serrage.', 'price' => 4900, 'colors' => ['Sable', 'Bleu nuit']],
                ],
            ],
            [
                'name' => 'Accessoires',
                'description' => 'Les détails qui complètent la silhouette.',
                'sizes' => ['Unique'],
                'products' => [
                    ['name' => 'Casquette Signal', 'description' => 'Casquette 6 panneaux en coton, logo brodé tonal et fermeture réglable.', 'price' => 2900, 'colors' => ['Noir', 'Écru']],
                    ['name' => 'Bonnet Fjord', 'description' => 'Bonnet côtelé en laine mérinos, doublé polaire.', 'price' => 2500, 'colors' => ['Anthracite', 'Sable']],
                    ['name' => 'Tote Bag Canvas', 'description' => 'Tote bag en toile épaisse 12 oz, anses renforcées et poche intérieure.', 'price' => 2200, 'colors' => ['Écru', 'Noir']],
                ],
            ],
        ];
    }
}
