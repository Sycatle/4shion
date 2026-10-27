<?php

declare(strict_types=1);

use App\Cart\CartManager;
use App\Livewire\Shop\CartPage;
use App\Livewire\Shop\ProductShow;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

it('adds the selected variant to the cart from the product page', function (): void {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create(['color' => 'Noir', 'size' => 'M', 'stock' => 5]);

    Livewire::test(ProductShow::class, ['product' => $product])
        ->call('selectSize', 'M')
        ->call('addToCart')
        ->assertSet('added', true)
        ->assertDispatched('cart-updated');

    expect(app(CartManager::class)->count())->toBe(1)
        ->and(app(CartManager::class)->items()->first()->product_variant_id)->toBe($variant->id);
});

it('asks for a size before adding to the cart', function (): void {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['stock' => 5]);

    Livewire::test(ProductShow::class, ['product' => $product])
        ->call('addToCart')
        ->assertSet('error', 'Veuillez choisir une taille.');

    expect(app(CartManager::class)->count())->toBe(0);
});

it('shows an empty cart message', function (): void {
    Livewire::test(CartPage::class)->assertSee('Votre panier est vide.');
});

it('changes quantities and removes lines', function (): void {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $item = app(CartManager::class)->add($variant);

    Livewire::test(CartPage::class)
        ->call('increment', $item->id)
        ->assertSee('2')
        ->call('remove', $item->id)
        ->assertSee('Votre panier est vide.');
});

it('stops incrementing at the available stock', function (): void {
    $variant = ProductVariant::factory()->create(['stock' => 1]);
    $item = app(CartManager::class)->add($variant);

    Livewire::test(CartPage::class)
        ->call('increment', $item->id)
        ->assertSet('error', 'Stock insuffisant : seulement 1 article(s) disponible(s).');
});

it('applies and rejects coupon codes', function (): void {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    app(CartManager::class)->add($variant);
    Coupon::factory()->create(['code' => 'BIENVENUE10']);

    Livewire::test(CartPage::class)
        ->set('couponCode', 'nope')
        ->call('applyCoupon')
        ->assertSet('couponError', 'Ce code promo est invalide ou expiré.')
        ->set('couponCode', 'bienvenue10')
        ->call('applyCoupon')
        ->assertSet('couponError', null)
        ->assertSee('BIENVENUE10');
});

it('throttles coupon attempts', function (): void {
    app(CartManager::class)->add(ProductVariant::factory()->create(['stock' => 5]));
    $page = Livewire::test(CartPage::class)->set('couponCode', 'nope');

    foreach (range(1, 5) as $attempt) {
        $page->call('applyCoupon')->assertSet('couponError', 'Ce code promo est invalide ou expiré.');
    }

    $page->call('applyCoupon');

    expect($page->get('couponError'))->toStartWith('Trop de tentatives.');
});
