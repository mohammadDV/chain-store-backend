<?php

namespace App\Policies\Concerns;

use Domain\AdminAccess\Services\AdminAccessService;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HandlesAdminResourceAuthorization
{
    abstract protected function permissionPrefix(): string;

    protected function isBrandScoped(): bool
    {
        return false;
    }

    protected function adminAccess(): AdminAccessService
    {
        return app(AdminAccessService::class);
    }

    public function viewAny(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->permissionPrefix().'.view')
            && $this->passesBrandScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->permissionPrefix().'.update')
            && $this->passesBrandScope($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->permissionPrefix().'.delete')
            && $this->passesBrandScope($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.delete');
    }

    protected function passesBrandScope(User $user, Model $model): bool
    {
        if (! $this->isBrandScoped()) {
            return true;
        }

        return $this->adminAccess()->canAccessModel($user, $model);
    }
}
