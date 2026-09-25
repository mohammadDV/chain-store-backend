<?php

use Domain\Notification\Models\Notification;
use Domain\Post\Models\Post;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('belongs to a role and has posts and notifications', function () {
    $this->seedRoles();
    $user = User::factory()->create(['role_id' => 2]);
    $post = Post::factory()->create(['user_id' => $user->id]);
    $notification = Notification::query()->create([
        'user_id' => $user->id,
        'title' => 'Hello',
        'content' => 'World',
        'status' => 1,
        'read' => 0,
    ]);

    expect($user->role())->toBeInstanceOf(BelongsTo::class)
        ->and($user->role)->toBeInstanceOf(Role::class)
        ->and($user->posts())->toBeInstanceOf(HasMany::class)
        ->and($user->posts->first()->is($post))->toBeTrue()
        ->and($user->notifications())->toBeInstanceOf(HasMany::class)
        ->and($user->notifications->first()->is($notification))->toBeTrue();
});

it('hashes passwords and hides sensitive attributes', function () {
    $user = User::factory()->create(['password' => 'SecretPass1!']);

    expect(Hash::check('SecretPass1!', $user->password))->toBeTrue()
        ->and($user->toArray())->not->toHaveKey('password')
        ->and($user->toArray())->not->toHaveKey('remember_token');
});

it('generates unique customer numbers', function () {
    $first = User::generateCustumerNumber();
    $second = User::generateCustumerNumber();

    expect($first)->toMatch('/^\d{10}$/')
        ->and($second)->toMatch('/^\d{10}$/')
        ->and($first)->not->toBe($second);
});

it('builds filament display name from available fields', function () {
    $full = User::factory()->make([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'nickname' => 'ada',
        'email' => 'ada@example.com',
    ]);
    $firstOnly = User::factory()->make([
        'first_name' => 'Ada',
        'last_name' => null,
        'nickname' => null,
        'email' => 'ada@example.com',
    ]);
    $nicknameOnly = User::factory()->make([
        'first_name' => null,
        'last_name' => null,
        'nickname' => 'ada',
        'email' => 'ada@example.com',
    ]);
    $emailOnly = User::factory()->make([
        'first_name' => null,
        'last_name' => null,
        'nickname' => null,
        'email' => 'ada@example.com',
    ]);
    $fallback = new User;
    $fallback->id = 99;
    $fallback->first_name = null;
    $fallback->last_name = null;
    $fallback->nickname = null;
    $fallback->email = null;

    expect($full->getFilamentName())->toBe('Ada Lovelace')
        ->and($full->getUserName())->toBe('Ada Lovelace')
        ->and($firstOnly->getFilamentName())->toBe('Ada')
        ->and($nicknameOnly->getFilamentName())->toBe('ada')
        ->and($emailOnly->getFilamentName())->toBe('ada@example.com')
        ->and($fallback->getFilamentName())->toBe('User #99');
});

it('allows panel access for level three users and configured super admins', function () {
    $admin = User::factory()->make(['level' => 3, 'email' => 'ops@example.com']);
    $super = User::factory()->make(['level' => 0, 'email' => 'admin@gmail.com']);
    $user = User::factory()->make(['level' => 0, 'email' => 'user@example.com']);
    $panel = Mockery::mock(Panel::class);

    expect($admin->canAccessPanel($panel))->toBeTrue()
        ->and($super->canAccessPanel($panel))->toBeTrue()
        ->and($user->canAccessPanel($panel))->toBeFalse();
});

it('exposes status name attribute', function () {
    $active = User::factory()->make(['status' => 1]);
    $inactive = User::factory()->make(['status' => 0]);

    expect($active->status_name)->toBe(__('site.Active'))
        ->and($inactive->status_name)->toBe(__('site.Inactive'));
});
