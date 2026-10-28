<?php

declare(strict_types=1);

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\CouponPerformance;
use App\Filament\Widgets\ShopStats;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('opens a record from the link Filament generates, whether it carries the slug, the number or the id', function (): void {
    $product = Product::factory()->create();
    $order = Order::factory()->paid()->create();
    $category = Category::factory()->create();

    $this->get(ProductResource::getUrl('edit', ['record' => $product]))->assertOk();
    $this->get('/admin/products/'.$product->id.'/edit')->assertOk();
    $this->get(OrderResource::getUrl('view', ['record' => $order]))->assertOk();
    $this->get('/admin/orders/'.$order->id)->assertOk();
    $this->get(CategoryResource::getUrl('index'))->assertOk();
    $this->get('/admin/products/unknown-slug/edit')->assertNotFound();
});

it('opens the customer file', function (): void {
    $customer = User::factory()->create();

    $this->get(CustomerResource::getUrl('view', ['record' => $customer]))->assertOk();
});

it('applies the dashboard period posted by the filter as a string', function (): void {
    Livewire::test(ShopStats::class, ['pageFilters' => ['days' => '365']])->assertSee('Sur 365 jours');
    Livewire::test(ShopStats::class, ['pageFilters' => ['days' => '7']])->assertSee('Sur 7 jours');
    Livewire::test(ShopStats::class)->assertSee('Sur 30 jours');
    Livewire::test(CouponPerformance::class, ['pageFilters' => ['days' => 'garbage']])->assertOk();
});
