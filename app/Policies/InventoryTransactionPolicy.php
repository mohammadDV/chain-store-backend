<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;

class InventoryTransactionPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function isBrandScoped(): bool
    {
        return true;
    }

    protected function permissionPrefix(): string
    {
        return 'inventory_transactions';
    }
}
