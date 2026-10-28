<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Livewire\Shop\ProductShow;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

it('shows an active product with its price', function (): void {
    $product = Product::factory()->create(['name' => 'Hoodie Atlas', 'base_price' => 8900]);
    ProductVariant::factory()->for($product)->create(['color' => 'Noir', 'size' => 'M']);

    $this->get(route('shop.show', $product))
        ->assertOk()
        ->assertSee('Hoodie Atlas')
        ->assertSee('89,00');
});

it('returns 404 for inactive products', function (): void {
    $product = Product::factory()->inactive()->create();

    $this->get(route('shop.show', $product))->assertNotFound();
});

it('preselects the first color that has stock', function (): void {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->outOfStock()->create(['color' => 'Noir', 'size' => 'M']);
    ProductVariant::factory()->for($product)->create(['color' => 'Écru', 'size' => 'M', 'stock' => 5]);

    Livewire::test(ProductShow::class, ['product' => $product])->assertSet('color', 'Écru');
});

it('keeps a sold-out size selectable so its back-in-stock alert can be requested, but not purchasable', function (): void {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->outOfStock()->create(['color' => 'Noir', 'size' => 'S']);
    ProductVariant::factory()->for($product)->create(['color' => 'Noir', 'size' => 'M', 'stock' => 5]);

    Livewire::test(ProductShow::class, ['product' => $product])
        ->call('selectSize', 'S')
        ->assertSet('size', 'S')
        ->call('addToCart')
        ->assertSet('added', false)
        ->call('selectSize', 'M')
        ->call('addToCart')
        ->assertSet('added', true);
});

it('only shows approved reviews', function (): void {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create();
    Review::factory()->for($product)->create(['body' => 'Avis publié']);
    Review::factory()->for($product)->pending()->create(['body' => 'Avis en attente']);

    $this->get(route('shop.show', $product))
        ->assertSee('Avis publié')
        ->assertDontSee('Avis en attente');
});

function purchase(User $user, Product $product, OrderStatus $status = OrderStatus::Paid): void
{
    $variant = ProductVariant::factory()->for($product)->create();
    $order = Order::factory()->for($user)->create(['status' => $status]);
    OrderItem::create([
        'order_id' => $order->id, 'product_variant_id' => $variant->id, 'product_name' => $product->name,
        'variant_label' => $variant->label(), 'sku' => $variant->sku, 'unit_price' => 1000, 'quantity' => 1, 'total' => 1000,
    ]);
}

it('lets a customer who bought the product leave a review pending moderation', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    purchase($user, $product);

    Livewire::actingAs($user)->test(ProductShow::class, ['product' => $product])
        ->assertSee('Donner mon avis')
        ->set('rating', 4)
        ->set('reviewBody', 'Très belle matière, coupe parfaite.')
        ->call('submitReview')
        ->assertHasNoErrors()
        ->assertSet('reviewSubmitted', true);

    $review = $product->reviews()->sole();
    expect($review->rating)->toBe(4)
        ->and($review->approved_at)->toBeNull()
        ->and($review->user_id)->toBe($user->id);
});

it('refuses reviews from customers who did not buy the product', function (): void {
    $product = Product::factory()->create();

    Livewire::actingAs(User::factory()->create())->test(ProductShow::class, ['product' => $product])
        ->assertDontSee('Donner mon avis')
        ->set('reviewBody', 'Un avis sans achat.')
        ->call('submitReview')
        ->assertForbidden();
});

it('refuses reviews for unpaid orders', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    purchase($user, $product, OrderStatus::Pending);

    expect($user->can('review', $product))->toBeFalse();
});

it('allows a single review per product', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    purchase($user, $product);
    Review::factory()->for($product)->for($user)->create();

    expect($user->can('review', $product))->toBeFalse();
});

it('validates the review content', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    purchase($user, $product);

    Livewire::actingAs($user)->test(ProductShow::class, ['product' => $product])
        ->set('reviewBody', 'court')
        ->set('rating', 9)
        ->call('submitReview')
        ->assertHasErrors(['reviewBody', 'rating']);
});
