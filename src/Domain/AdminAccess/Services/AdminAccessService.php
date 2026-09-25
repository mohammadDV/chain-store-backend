<?php

namespace Domain\AdminAccess\Services;

use Domain\AdminAccess\AdminPermission;
use Domain\Brand\Models\Banner;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Color;
use Domain\Product\Models\InventoryTransaction;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Review\Models\Review;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdminAccessService
{
    public function isSuperAdmin(User $user): bool
    {
        $email = strtolower((string) $user->email);

        return $email !== '' && in_array($email, config('admin.super_admin_emails', []), true);
    }

    /**
     * Brand IDs the user may access. Null means all brands (super admin).
     *
     * @return list<int>|null
     */
    public function allowedBrandIds(User $user): ?array
    {
        if ($this->isSuperAdmin($user)) {
            return null;
        }

        return $user->adminBrands()
            ->pluck('brands.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function canAccessBrand(User $user, ?int $brandId): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($brandId === null) {
            return false;
        }

        $allowed = $this->allowedBrandIds($user);

        return is_array($allowed) && in_array($brandId, $allowed, true);
    }

    /**
     * @param  list<int>  $brandIds
     */
    public function canAccessAnyBrand(User $user, array $brandIds): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $allowed = $this->allowedBrandIds($user) ?? [];

        if ($allowed === [] || $brandIds === []) {
            return false;
        }

        return count(array_intersect($allowed, array_map('intval', $brandIds))) > 0;
    }

    public function canAccessModel(User $user, Model $model): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return match (true) {
            $model instanceof Brand => $this->canAccessBrand($user, (int) $model->getKey()),
            $model instanceof Product, $model instanceof Banner => $this->canAccessBrand(
                $user,
                $this->nullableIntAttribute($model, 'brand_id')
            ),
            $model instanceof Category => $this->canAccessAnyBrand(
                $user,
                $model->brands()->pluck('brands.id')->map(fn ($id) => (int) $id)->all()
            ),
            $model instanceof Color => $this->canAccessAnyBrand(
                $user,
                $model->brands()->pluck('brands.id')->map(fn ($id) => (int) $id)->all()
            ),
            $model instanceof Order => $this->canAccessAnyBrand(
                $user,
                $model->products()->pluck('products.brand_id')->map(fn ($id) => (int) $id)->all()
            ),
            $model instanceof Review, $model instanceof InventoryTransaction => $this->canAccessBrand(
                $user,
                $this->relatedProductBrandId($model)
            ),
            default => true,
        };
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeBrandQuery(Builder $query, User $user, string $strategy): Builder
    {
        if ($this->isSuperAdmin($user)) {
            return $query;
        }

        $brandIds = $this->allowedBrandIds($user) ?? [];

        if ($brandIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return match ($strategy) {
            'id' => $query->whereIn($query->getModel()->getTable().'.id', $brandIds),
            'brand_id' => $query->whereIn($query->getModel()->getTable().'.brand_id', $brandIds),
            'brands' => $query->whereHas('brands', fn (Builder $q) => $q->whereIn('brands.id', $brandIds)),
            'products' => $query->whereHas('products', fn (Builder $q) => $q->whereIn('products.brand_id', $brandIds)),
            'product' => $query->whereHas('product', fn (Builder $q) => $q->whereIn('products.brand_id', $brandIds)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Brands available for selects in Filament forms.
     *
     * @return Builder<Brand>
     */
    public function brandsQueryFor(User $user): Builder
    {
        $query = Brand::query()->orderBy('title');

        if ($this->isSuperAdmin($user)) {
            return $query;
        }

        $brandIds = $this->allowedBrandIds($user) ?? [];

        if ($brandIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', $brandIds);
    }

    /**
     * @param  list<string>  $permissions
     * @param  list<int>  $brandIds
     */
    public function syncForUser(User $user, array $permissions, array $brandIds): void
    {
        $validPermissions = array_values(array_intersect($permissions, AdminPermission::all()));
        $validBrandIds = Brand::query()
            ->whereIn('id', array_map('intval', $brandIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($user, $validPermissions, $validBrandIds) {
            $user->syncPermissions($validPermissions);
            $user->adminBrands()->sync($validBrandIds);

            $user->forceFill([
                'level' => $validPermissions !== [] ? 3 : 0,
            ])->save();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function ensurePermissionsExist(): void
    {
        foreach (AdminPermission::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function nullableIntAttribute(Model $model, string $attribute): ?int
    {
        $value = $model->getAttribute($attribute);

        return $value === null ? null : (int) $value;
    }

    private function relatedProductBrandId(Model $model): ?int
    {
        $product = $model->getAttribute('product');

        if (! $product instanceof Product) {
            return null;
        }

        return $this->nullableIntAttribute($product, 'brand_id');
    }
}
