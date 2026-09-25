<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminResourceAuthorization;

class TicketMessagePolicy
{
    use HandlesAdminResourceAuthorization;

    protected function permissionPrefix(): string
    {
        return 'ticket_messages';
    }
}
