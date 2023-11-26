<?php

namespace App\Exception;

class AccountNotFoundException extends TransactionException
{
    public function __construct(int $accountId)
    {
        parent::__construct('Account #'.$accountId.' not found.');
    }
}
