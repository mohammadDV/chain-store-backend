<?php

namespace Domain\Product\Exceptions;

use RuntimeException;

class ProductScraperException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0)
    {
        parent::__construct($message, $status);
    }
}
