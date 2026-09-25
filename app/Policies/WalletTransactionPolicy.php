<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;

class WalletTransactionPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function permissionPrefix(): string
    {
        return 'wallet_transactions';
    }
}
