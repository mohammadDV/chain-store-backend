<?php

namespace Domain\Product\Enums;

final class InventoryTransactionSource
{
    public const System = 'system';

    public const Admin = 'admin';

    public const Scraper = 'scraper';

    public const Order = 'order';

    private function __construct() {}
}
