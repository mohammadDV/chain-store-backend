<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;
use Domain\AdminAccess\AdminPermission;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class ManualOrderPolicy
{
    use HandlesAdminResourceAuthorization;

    public function changeStatus(User $user, Model $model): bool
    {
        return $user->can(AdminPermission::MANUAL_ORDERS_CHANGE_STATUS);
    }

    protected function permissionPrefix(): string
    {
        return 'manual_orders';
    }
}
