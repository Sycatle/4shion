<?php

declare(strict_types=1);

use App\Livewire\Shop\ProductShow;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use Livewire\Livewire;

function soldOutProduct(bool $preorder = false): Product
{
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['color' => 'Noir', 'size' => 'M', 'stock' => 0, 'preorder_enabled' => $preorder]);

    return $product;
}

it('lets a visitor ask to be told when a sold-out size is back', function (): void {
    $product = soldOutProduct();

    Livewire::test(ProductShow::class, ['product' => $product])
        ->call('selectSize', 'M')
        ->set('alertEmail', 'Client@Example.test')
        ->call('subscribeToAlert')
        ->assertHasNoErrors()
        ->assertSet('alertSubscribed', true);

    expect(StockAlert::query()->sole()->email)->toBe('client@example.test');
});

it('does not duplicate an alert and re-arms one that was already sent', function (): void {
    $product = soldOutProduct();
    $variant = $product->variants()->sole();
    StockAlert::create(['product_variant_id' => $variant->id, 'email' => 'client@example.test', 'notified_at' => now()->subDay()]);

    Livewire::test(ProductShow::class, ['product' => $product])
        ->call('selectSize', 'M')
        ->set('alertEmail', 'client@example.test')
        ->call('subscribeToAlert');

    expect(StockAlert::query()->sole()->notified_at)->toBeNull();
});

it('validates the e-mail of an alert', function (): void {
    Livewire::test(ProductShow::class, ['product' => soldOutProduct()])
        ->call('selectSize', 'M')
        ->set('alertEmail', 'not-an-email')
        ->call('subscribeToAlert')
        ->assertHasErrors(['alertEmail']);
});

it('offers pre-ordering instead of an alert when the variant accepts it', function (): void {
    $product = soldOutProduct(preorder: true);

    Livewire::test(ProductShow::class, ['product' => $product])
        ->call('selectSize', 'M')
        ->assertSee('Précommande')
        ->assertDontSee('Me prévenir')
        ->call('addToCart')
        ->assertSet('added', true);
});

it('refuses to add a sold-out variant to the cart', function (): void {
    Livewire::test(ProductShow::class, ['product' => soldOutProduct()])
        ->call('selectSize', 'M')
        ->assertSee('Me prévenir')
        ->call('addToCart')
        ->assertSet('added', false)
        ->assertSet('error', 'Cet article est actuellement épuisé.');
});

it('strikes the regular price through during a sale', function (): void {
    $product = Product::factory()->onSale(20)->create(['base_price' => 10000]);
    ProductVariant::factory()->for($product)->create(['color' => 'Noir', 'size' => 'M', 'stock' => 3]);

    Livewire::test(ProductShow::class, ['product' => $product])
        ->call('selectSize', 'M')
        ->assertSee('80,00')
        ->assertSee('100,00')
        ->assertSee('-20 %');
});
