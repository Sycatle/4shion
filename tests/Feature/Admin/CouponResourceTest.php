<?php

use App\Enums\DiscountType;
use App\Filament\Resources\Coupons\Pages\ManageCoupons;
use App\Models\Coupon;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('creates a percentage coupon with an uppercase code', function () {
    Livewire::test(ManageCoupons::class)
        ->callAction('create', data: ['code' => ' ete20 ', 'type' => 'percent', 'value' => 20, 'is_active' => true])
        ->assertHasNoFormErrors();

    $coupon = Coupon::firstWhere('code', 'ETE20');
    expect($coupon->type)->toBe(DiscountType::Percent)->and($coupon->value)->toBe(20);
});

it('stores fixed amounts and minimums in cents', function () {
    Livewire::test(ManageCoupons::class)
        ->callAction('create', data: ['code' => 'FIVE', 'type' => 'fixed', 'value' => '5.50', 'min_subtotal' => '30', 'is_active' => true]);

    $coupon = Coupon::firstWhere('code', 'FIVE');
    expect($coupon->value)->toBe(550)->and($coupon->min_subtotal)->toBe(3000);
});

it('rejects percentages above 100', function () {
    Livewire::test(ManageCoupons::class)
        ->callAction('create', data: ['code' => 'TOOMUCH', 'type' => 'percent', 'value' => 150])
        ->assertHasFormErrors(['value']);
});

it('lists coupons', function () {
    $coupons = Coupon::factory()->count(2)->create();

    Livewire::test(ManageCoupons::class)->assertCanSeeTableRecords($coupons);
});

it('shows fixed amounts in euros when editing', function () {
    $coupon = Coupon::factory()->fixed(2000)->create();

    Livewire::test(ManageCoupons::class)
        ->mountAction(TestAction::make('edit')->table($coupon))
        ->assertSchemaStateSet(['value' => 20.0]);
});
