<?php

namespace App\Exceptions;

use RuntimeException;

class TransactionAlreadyReversedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'A transação já foi revertida.'
        );
    }
}