<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;

class ColorPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function isBrandScoped(): bool
    {
        return true;
    }

    protected function permissionPrefix(): string
    {
        return 'colors';
    }
}
