<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('the account menu is rendered for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Déconnexion');
});

test('users can logout', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
});

test('guests cannot logout', function () {
    $this->post(route('logout'))->assertRedirect(route('login'));
});
