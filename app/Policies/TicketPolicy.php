<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;

class TicketPolicy
{
    use HandlesAdminResourceAuthorization;

    protected function permissionPrefix(): string
    {
        return 'tickets';
    }
}
