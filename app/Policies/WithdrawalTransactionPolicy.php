<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;
use Domain\AdminAccess\AdminPermission;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class WithdrawalTransactionPolicy
{
    use HandlesAdminResourceAuthorization;

    public function approve(User $user, Model $model): bool
    {
        return $user->can(AdminPermission::WITHDRAWALS_APPROVE);
    }

    public function reject(User $user, Model $model): bool
    {
        return $user->can(AdminPermission::WITHDRAWALS_REJECT);
    }

    protected function permissionPrefix(): string
    {
        return 'withdrawals';
    }
}
