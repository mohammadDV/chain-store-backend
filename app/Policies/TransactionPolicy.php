<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;

class TransactionPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function permissionPrefix(): string
    {
        return 'transactions';
    }
}
