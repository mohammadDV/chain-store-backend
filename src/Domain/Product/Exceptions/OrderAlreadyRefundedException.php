<?php

namespace Domain\Product\Exceptions;

use RuntimeException;

class OrderAlreadyRefundedException extends RuntimeException
{
    public function __construct(string $message = 'Order is already refunded and cannot change status.')
    {
        parent::__construct($message);
    }
}
