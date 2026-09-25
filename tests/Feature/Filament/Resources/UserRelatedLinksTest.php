<?php

use App\Filament\Resources\UserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds relative related resource deep links filtered by user id', function () {
    $links = UserResource::relatedResourceLinks(42);

    expect($links)->toHaveKeys([
        'wallet',
        'wallet_transactions',
        'withdrawals',
        'orders',
        'transactions',
        'reviews',
        'tickets',
        'notifications',
    ]);

    foreach ($links as $link) {
        expect($link['url'])
            ->toStartWith('/')
            ->not->toStartWith('http')
            ->toContain('filters')
            ->not->toContain('tableFilters')
            ->toContain('user_id')
            ->toContain('42');
    }
});
