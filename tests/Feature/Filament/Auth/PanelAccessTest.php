<?php

use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect();
});

it('forbids non-admin users from the admin panel', function () {
    $user = User::factory()->create(['level' => 0, 'role_id' => 2, 'status' => 1]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('allows level-3 admins into the admin panel', function () {
    $this->actingAsAdmin();

    $this->get('/admin')->assertOk();
});

it('allows configured super admin email into the admin panel even without level 3', function () {
    $user = User::factory()->create([
        'email' => 'admin@gmail.com',
        'level' => 0,
        'status' => 1,
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});
