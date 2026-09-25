<?php

namespace Domain\Product\Enums;

enum InventoryTransactionType: string
{
    case Sale = 'sale';
    case Return = 'return';
    case Adjust = 'adjust';
    case Purchase = 'purchase';
    case Reserve = 'reserve';
    case Release = 'release';
}
