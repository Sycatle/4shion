<?php

declare(strict_types=1);

use App\Livewire\Account\AddressBook;
use App\Models\Address;
use App\Models\User;
use Livewire\Livewire;

function fillAddress($component)
{
    return $component
        ->set('firstName', 'Camille')
        ->set('lastName', 'Martin')
        ->set('line1', '12 rue des Lilas')
        ->set('postalCode', '75011')
        ->set('city', 'Paris');
}

it('creates addresses and makes the first one the default', function (): void {
    $user = User::factory()->create();

    fillAddress(Livewire::actingAs($user)->test(AddressBook::class)->call('create'))
        ->call('save')
        ->assertHasNoErrors();

    expect($user->addresses()->sole()->is_default)->toBeTrue();
});

it('validates the postal code', function (): void {
    fillAddress(Livewire::actingAs(User::factory()->create())->test(AddressBook::class)->call('create'))
        ->set('postalCode', 'ABCDE')
        ->call('save')
        ->assertHasErrors(['postalCode']);
});

it('edits an address', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->for($user)->create(['city' => 'Lyon']);

    Livewire::actingAs($user)->test(AddressBook::class)
        ->call('edit', $address->id)
        ->set('city', 'Nantes')
        ->call('save');

    expect($address->fresh()->city)->toBe('Nantes');
});

it('switches the default address and promotes another one on deletion', function (): void {
    $user = User::factory()->create();
    $first = Address::factory()->for($user)->create(['is_default' => true]);
    $second = Address::factory()->for($user)->create();

    $component = Livewire::actingAs($user)->test(AddressBook::class)->call('makeDefault', $second->id);
    expect($second->fresh()->is_default)->toBeTrue()->and($first->fresh()->is_default)->toBeFalse();

    $component->call('delete', $second->id);
    expect($first->fresh()->is_default)->toBeTrue();
});

it('cannot touch the address of another customer', function (): void {
    $address = Address::factory()->create();

    Livewire::actingAs(User::factory()->create())->test(AddressBook::class)
        ->call('delete', $address->id)
        ->assertNotFound();

    expect($address->fresh())->not->toBeNull();
});
