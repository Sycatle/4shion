<?php

declare(strict_types=1);

use App\Enums\StockMovementReason;
use App\Filament\Resources\GiftCards\Pages\ManageGiftCards;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Filament\Resources\Promotions\Pages\ManagePromotions;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\GiftCard;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\StockMovement;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

function variantsManager(Product $product): Testable
{
    return Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class]);
}

it('adjusts stock through the ledger and attributes the movement to the admin', function (): void {
    $variant = ProductVariant::factory()->create(['stock' => 4]);

    variantsManager($variant->product)
        ->callAction(TestAction::make('adjustStock')->table($variant), data: ['delta' => 6, 'reason' => 'restock', 'note' => 'Réassort'])
        ->assertNotified('Stock mis à jour');

    $movement = StockMovement::query()->sole();

    expect($variant->fresh()->stock)->toBe(10)
        ->and($movement->reason)->toBe(StockMovementReason::Restock)
        ->and($movement->user_id)->toBe($this->admin->id)
        ->and($movement->note)->toBe('Réassort');
});

it('reports an outgoing adjustment larger than the stock instead of failing', function (): void {
    $variant = ProductVariant::factory()->create(['stock' => 2]);

    variantsManager($variant->product)
        ->callAction(TestAction::make('adjustStock')->table($variant), data: ['delta' => -5, 'reason' => 'adjustment'])
        ->assertNotified('Stock insuffisant pour cette sortie');

    expect($variant->fresh()->stock)->toBe(2)->and(StockMovement::query()->count())->toBe(0);
});

it('logs the initial stock of a new variant', function (): void {
    $product = Product::factory()->create();

    variantsManager($product)
        ->callTableAction('create', data: ['sku' => 'INIT-1', 'size' => 'M', 'color' => 'Noir', 'stock' => 7, 'preorder_enabled' => true, 'low_stock_threshold' => 2])
        ->assertHasNoTableActionErrors();

    $variant = $product->variants()->where('sku', 'INIT-1')->sole();

    expect($variant->stock)->toBe(7)
        ->and($variant->preorder_enabled)->toBeTrue()
        ->and($variant->lowStockThreshold())->toBe(2)
        ->and($variant->stockMovements()->sole()->note)->toBe('Stock initial');
});

it('lists the stock movements', function (): void {
    $variant = ProductVariant::factory()->create(['stock' => 1]);
    $movement = StockMovement::create(['product_variant_id' => $variant->id, 'reason' => StockMovementReason::Restock, 'quantity' => 1, 'stock_after' => 1]);

    Livewire::test(ListStockMovements::class)->assertCanSeeTableRecords([$movement]);
});

it('creates a gift card whose balance equals its amount', function (): void {
    Livewire::test(ManageGiftCards::class)
        ->callAction('create', data: ['code' => 'gc-test-code-0001', 'initial_value' => '50', 'is_active' => true])
        ->assertHasNoFormErrors();

    $card = GiftCard::query()->sole();

    expect($card->code)->toBe('GC-TEST-CODE-0001')->and($card->initial_value)->toBe(5000)->and($card->balance)->toBe(5000);
});

it('creates a spend threshold promotion', function (): void {
    Livewire::test(ManagePromotions::class)
        ->callAction('create', data: ['name' => '-10 % dès 150 €', 'type' => 'spend_threshold', 'min_subtotal' => '150', 'percent' => 10, 'is_active' => true])
        ->assertHasNoFormErrors();

    expect(Promotion::query()->sole()->min_subtotal)->toBe(15000);
});

it('requires a quantity for a buy-X promotion', function (): void {
    Livewire::test(ManagePromotions::class)
        ->callAction('create', data: ['name' => '3 pour 2', 'type' => 'buy_x_get_cheapest_free', 'is_active' => true])
        ->assertHasFormErrors(['buy_quantity']);
});
