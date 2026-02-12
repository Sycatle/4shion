<?php

use App\Livewire\Shop\ProductShow;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use Livewire\Livewire;

it('shows an active product with its price', function () {
    $product = Product::factory()->create(['name' => 'Hoodie Atlas', 'base_price' => 8900]);
    ProductVariant::factory()->for($product)->create(['color' => 'Noir', 'size' => 'M']);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertSee('Hoodie Atlas')
        ->assertSee('89,00');
});

it('returns 404 for inactive products', function () {
    $product = Product::factory()->inactive()->create();

    $this->get(route('shop.show', $product))->assertNotFound();
});

it('preselects the first color that has stock', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->outOfStock()->create(['color' => 'Noir', 'size' => 'M']);
    ProductVariant::factory()->for($product)->create(['color' => 'Écru', 'size' => 'M', 'stock' => 5]);

    Livewire::test(ProductShow::class, ['product' => $product])->assertSet('color', 'Écru');
});

it('does not select a size that is out of stock', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->outOfStock()->create(['color' => 'Noir', 'size' => 'S']);
    ProductVariant::factory()->for($product)->create(['color' => 'Noir', 'size' => 'M', 'stock' => 5]);

    Livewire::test(ProductShow::class, ['product' => $product])
        ->call('selectSize', 'S')
        ->assertSet('size', null)
        ->call('selectSize', 'M')
        ->assertSet('size', 'M');
});

it('only shows approved reviews', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create();
    Review::factory()->for($product)->create(['body' => 'Avis publié']);
    Review::factory()->for($product)->pending()->create(['body' => 'Avis en attente']);

    $this->get(route('shop.show', $product))
        ->assertSee('Avis publié')
        ->assertDontSee('Avis en attente');
});
