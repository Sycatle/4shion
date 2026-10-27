<?php

use App\Filament\Widgets\LowStockVariants;
use App\Filament\Widgets\ShopStats;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('renders the dashboard without the default Filament widgets', function () {
    $this->get('/admin')->assertOk()->assertSee('4shion')->assertDontSee('Welcome');
});

it('summarises recent paid revenue', function () {
    Order::factory()->paid()->totalling(10000)->create();
    Order::factory()->paid()->totalling(5000)->create(['paid_at' => now()->subDays(45)]);
    Order::factory()->totalling(9999)->create();

    Livewire::test(ShopStats::class)
        ->assertSee('100,00')
        ->assertDontSee('150,00');
});

it('lists only low-stock variants of active products', function () {
    $low = ProductVariant::factory()->create(['stock' => 1]);
    $healthy = ProductVariant::factory()->create(['stock' => 50]);

    Livewire::test(LowStockVariants::class)
        ->assertCanSeeTableRecords([$low])
        ->assertCanNotSeeTableRecords([$healthy]);
});
