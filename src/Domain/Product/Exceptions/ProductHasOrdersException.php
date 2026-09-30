<?php

namespace Domain\Product\Exceptions;

use RuntimeException;

class ProductHasOrdersException extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('site.product_cannot_delete_has_orders'));
    }
}
