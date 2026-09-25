<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;

class BrandPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function isBrandScoped(): bool
    {
        return true;
    }

    protected function permissionPrefix(): string
    {
        return 'brands';
    }
}
