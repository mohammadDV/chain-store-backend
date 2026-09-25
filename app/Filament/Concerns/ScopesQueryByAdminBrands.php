<?php

namespace App\Filament\Concerns;

use Domain\AdminAccess\Services\AdminAccessService;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesQueryByAdminBrands
{
    /**
     * Strategy passed to AdminAccessService::scopeBrandQuery.
     * One of: id, brand_id, brands, products, product.
     */
    abstract protected static function brandScopeStrategy(): string;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return app(AdminAccessService::class)
            ->scopeBrandQuery($query, $user, static::brandScopeStrategy());
    }
}
