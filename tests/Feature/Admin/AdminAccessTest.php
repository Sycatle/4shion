<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests to the admin login', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('forbids customers from the admin panel', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('lets admins into the panel', function (): void {
    $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin')->assertOk();
});

it('falls back to English for Filament strings missing from the French translation', function (): void {
    app()->setLocale('fr');

    expect(__('filament-panels::layout.actions.logout.label'))->toBe('Déconnexion')
        ->and(__('filament-panels::layout.skip_to_content.label'))->toBe('Skip to content')
        ->and(__('Taille'))->toBe('Taille');

    $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin')
        ->assertDontSee('filament-panels::', false);
});
