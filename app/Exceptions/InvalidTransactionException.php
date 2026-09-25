<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidTransactionException extends RuntimeException
{
    public function __construct(
        string $message = 'Transação inválida.'
    ) {
        parent::__construct($message);
    }
}
