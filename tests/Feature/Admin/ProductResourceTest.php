<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('lists products', function () {
    $products = Product::factory()->count(3)->create();

    Livewire::test(ListProducts::class)->assertCanSeeTableRecords($products);
});

it('creates a product and stores the price in cents', function () {
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Hoodie Atlas',
            'slug' => 'hoodie-atlas',
            'category_id' => $category->id,
            'base_price' => '89.90',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::firstWhere('slug', 'hoodie-atlas')->base_price)->toBe(8990);
});

it('shows the price in euros when editing', function () {
    $product = Product::factory()->create(['base_price' => 8990]);

    Livewire::test(EditProduct::class, ['record' => $product->id])
        ->assertFormSet(['base_price' => 89.9]);
});

it('requires a unique slug', function () {
    Product::factory()->create(['slug' => 'taken']);

    Livewire::test(CreateProduct::class)
        ->fillForm(['name' => 'Autre', 'slug' => 'taken', 'category_id' => Category::factory()->create()->id, 'base_price' => 10])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('manages variants of a product', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create(['stock' => 4, 'size' => 'XL', 'color' => 'Ecru']);

    Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
        ->assertCanSeeTableRecords([$variant])
        ->callTableAction('create', data: [
            'sku' => 'NEW-SKU', 'size' => 'M', 'color' => 'Noir', 'stock' => 12, 'price_override' => '79.00',
        ])
        ->assertHasNoTableActionErrors();

    expect($product->variants()->where('sku', 'NEW-SKU')->sole()->price_override)->toBe(7900);
});
