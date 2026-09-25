<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;
use Domain\AdminAccess\AdminPermission;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function isBrandScoped(): bool
    {
        return true;
    }

    public function changeStatus(User $user, Model $model): bool
    {
        return $user->can(AdminPermission::ORDERS_CHANGE_STATUS)
            && $this->passesBrandScope($user, $model);
    }

    public function refund(User $user, Model $model): bool
    {
        return $user->can(AdminPermission::ORDERS_REFUND)
            && $this->passesBrandScope($user, $model);
    }

    protected function permissionPrefix(): string
    {
        return 'orders';
    }
}
