<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;

class ProductPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function isBrandScoped(): bool
    {
        return true;
    }

    protected function permissionPrefix(): string
    {
        return 'products';
    }
}
