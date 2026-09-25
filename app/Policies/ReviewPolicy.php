<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;
use Domain\AdminAccess\AdminPermission;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReviewPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function isBrandScoped(): bool
    {
        return true;
    }

    public function approve(User $user, Model $model): bool
    {
        return $user->can(AdminPermission::REVIEWS_APPROVE)
            && $this->passesBrandScope($user, $model);
    }

    protected function permissionPrefix(): string
    {
        return 'reviews';
    }
}
