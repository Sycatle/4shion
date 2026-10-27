<?php

use App\Cart\CartManager;
use App\Enums\OrderStatus;
use App\Livewire\Shop\CheckoutPage;
use App\Livewire\Shop\CheckoutSuccess;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('requires authentication to check out', function () {
    $this->get(route('checkout'))->assertRedirect(route('login'));
});

it('sends customers with an empty cart back to the cart', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CheckoutPage::class)->assertRedirect(route('cart'));
});

it('validates the shipping address', function () {
    $this->actingAs(User::factory()->create());
    cartWith();

    Livewire::test(CheckoutPage::class)
        ->set('firstName', '')
        ->set('postalCode', '7501')
        ->call('submit')
        ->assertHasErrors(['firstName', 'line1', 'city', 'postalCode']);
});

it('creates the order and redirects to the payment page', function () {
    fakeGateway();
    $user = User::factory()->create();
    $this->actingAs($user);
    cartWith();

    Livewire::test(CheckoutPage::class)
        ->set('firstName', 'Camille')
        ->set('lastName', 'Martin')
        ->set('line1', '12 rue des Lilas')
        ->set('postalCode', '75011')
        ->set('city', 'Paris')
        ->set('acceptTerms', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect('https://checkout.test/pay/'.$user->orders()->sole()->id);

    expect($user->addresses()->sole()->city)->toBe('Paris')
        ->and($user->addresses()->sole()->is_default)->toBeTrue();
});

it('requires the terms to be accepted', function () {
    fakeGateway();
    $user = User::factory()->create();
    $this->actingAs($user);
    cartWith();

    Livewire::test(CheckoutPage::class)
        ->set('firstName', 'Camille')
        ->set('lastName', 'Martin')
        ->set('line1', '12 rue des Lilas')
        ->set('postalCode', '75011')
        ->set('city', 'Paris')
        ->call('submit')
        ->assertHasErrors(['acceptTerms' => 'accepted'])
        ->assertDispatched('checkout-invalid');

    expect($user->orders()->count())->toBe(0);
});

it('shows a friendly error when the payment provider is unavailable', function () {
    fakeGateway()->fails = true;
    $this->actingAs(User::factory()->create());
    cartWith();

    Livewire::test(CheckoutPage::class)
        ->set('firstName', 'Camille')
        ->set('lastName', 'Martin')
        ->set('line1', '12 rue des Lilas')
        ->set('postalCode', '75011')
        ->set('city', 'Paris')
        ->set('acceptTerms', true)
        ->call('submit')
        ->assertSet('error', 'Le paiement est momentanément indisponible. Veuillez réessayer dans quelques instants.');
});

it('confirms the payment on the success page even if the webhook is late', function () {
    $gateway = fakeGateway();
    $user = User::factory()->create();
    $this->actingAs($user);
    $order = pendingOrder($user);
    $order->update(['stripe_session_id' => 'cs_live_1']);

    $gateway->sessionPaid = true;

    Livewire::withQueryParams(['session_id' => 'cs_live_1'])
        ->test(CheckoutSuccess::class)
        ->assertSee('Paiement en cours')
        ->call('refreshStatus')
        ->assertSee('Commande confirmée');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('hides other customers orders on the success page', function () {
    fakeGateway();
    $order = pendingOrder(User::factory()->create());
    $order->update(['stripe_session_id' => 'cs_live_1']);

    $this->actingAs(User::factory()->create())
        ->get(route('checkout.success', ['session_id' => 'cs_live_1']))
        ->assertNotFound();
});

it('restores the cart when the customer cancels the payment', function () {
    fakeGateway();
    $user = User::factory()->create();
    $this->actingAs($user);
    $order = pendingOrder($user, quantity: 2);

    $this->get(route('checkout.cancel', $order))->assertRedirect(route('cart'));

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(app(CartManager::class)->count())->toBe(2);
});

it('does not let a customer cancel someone else\'s order', function () {
    fakeGateway();
    $order = pendingOrder(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->get(route('checkout.cancel', $order))
        ->assertNotFound();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('throttles repeated checkout attempts', function () {
    fakeGateway();
    $user = User::factory()->create();
    $this->actingAs($user);
    RateLimiter::increment('checkout:'.$user->id, amount: 5);
    cartWith();

    Livewire::test(CheckoutPage::class)
        ->set('firstName', 'Camille')
        ->set('lastName', 'Martin')
        ->set('line1', '12 rue des Lilas')
        ->set('postalCode', '75011')
        ->set('city', 'Paris')
        ->set('acceptTerms', true)
        ->call('submit')
        ->assertNoRedirect();

    expect($user->orders()->exists())->toBeFalse();
});
