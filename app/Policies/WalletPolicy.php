<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;

class WalletPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function permissionPrefix(): string
    {
        return 'wallets';
    }
}
