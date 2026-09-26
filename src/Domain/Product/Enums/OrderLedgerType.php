<?php

namespace Domain\Product\Enums;

enum OrderLedgerType: string
{
    case Created = 'created';
    case Paid = 'paid';
    case Expired = 'expired';
    case StatusChanged = 'status_changed';
    case Refunded = 'refunded';
    case LineRefunded = 'line_refunded';
}
