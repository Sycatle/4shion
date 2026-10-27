<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests to the admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('forbids customers from the admin panel', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('lets admins into the panel', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin')->assertOk();
});
