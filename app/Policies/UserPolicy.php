<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;
use Domain\AdminAccess\AdminPermission;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy
{
    use HandlesAdminResourceAuthorization;

    public function managePermissions(User $user, Model $model): bool
    {
        return $user->can(AdminPermission::USERS_MANAGE_PERMISSIONS);
    }

    protected function permissionPrefix(): string
    {
        return 'users';
    }
}
