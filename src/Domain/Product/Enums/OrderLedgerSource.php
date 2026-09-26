<?php

namespace Domain\Product\Enums;

final class OrderLedgerSource
{
    public const System = 'system';

    public const Admin = 'admin';

    public const Customer = 'customer';

    private function __construct() {}
}
