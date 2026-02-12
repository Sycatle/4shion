<?php

use App\Livewire\Shop\ProductListing;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

it('lists only active products', function () {
    Product::factory()->create(['name' => 'Hoodie Visible']);
    Product::factory()->inactive()->create(['name' => 'Hoodie Caché']);

    Livewire::test(ProductListing::class)
        ->assertSee('Hoodie Visible')
        ->assertDontSee('Hoodie Caché');
});

it('filters by category', function () {
    $hoodies = Category::factory()->create(['slug' => 'hoodies']);
    Product::factory()->for($hoodies)->create(['name' => 'Produit Hoodie']);
    Product::factory()->create(['name' => 'Produit Autre']);

    Livewire::test(ProductListing::class)
        ->set('category', 'hoodies')
        ->assertSee('Produit Hoodie')
        ->assertDontSee('Produit Autre');
});

it('filters by size and color', function () {
    $match = Product::factory()->create(['name' => 'Produit Match']);
    ProductVariant::factory()->for($match)->create(['size' => 'M', 'color' => 'Noir']);

    $other = Product::factory()->create(['name' => 'Produit Autre']);
    ProductVariant::factory()->for($other)->create(['size' => 'XL', 'color' => 'Sable']);

    Livewire::test(ProductListing::class)
        ->set('sizes', ['M'])
        ->set('colors', ['Noir'])
        ->assertSee('Produit Match')
        ->assertDontSee('Produit Autre');
});

it('filters by price range expressed in euros', function () {
    Product::factory()->create(['name' => 'Produit Cher', 'base_price' => 20000]);
    Product::factory()->create(['name' => 'Produit Abordable', 'base_price' => 3000]);

    Livewire::test(ProductListing::class)
        ->set('maxPrice', 50)
        ->assertSee('Produit Abordable')
        ->assertDontSee('Produit Cher');
});

it('sorts by price', function () {
    Product::factory()->create(['name' => 'Produit B', 'base_price' => 9000]);
    Product::factory()->create(['name' => 'Produit A', 'base_price' => 3000]);

    Livewire::test(ProductListing::class)
        ->set('sort', 'price_asc')
        ->assertSeeInOrder(['Produit A', 'Produit B']);
});

it('searches products with Scout', function () {
    Product::factory()->create(['name' => 'Hoodie Atlas', 'description' => 'Molleton épais']);
    Product::factory()->create(['name' => 'Casquette Signal', 'description' => 'Coton']);

    Livewire::test(ProductListing::class)
        ->set('search', 'Atlas')
        ->assertSee('Hoodie Atlas')
        ->assertDontSee('Casquette Signal');
});
