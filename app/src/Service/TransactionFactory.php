<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Exception\AccountNotFoundException;
use App\Exception\TransactionException;
use App\Repository\AccountRepository;
use Doctrine\DBAL\LockMode;

class TransactionFactory
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly CurrencyConverter $currencyConverter
    ) {
    }

    public function create(
        int $sourceAccountId,
        int $targetAccountId,
        string $currency,
        int $amount
    ): Transaction {
        $source = $this->accountRepository->find($sourceAccountId, LockMode::PESSIMISTIC_WRITE);
        $target = $this->accountRepository->find($targetAccountId, LockMode::PESSIMISTIC_WRITE);

        if (is_null($source)) {
            throw new AccountNotFoundException($sourceAccountId);
        }
        if (is_null($target)) {
            throw new AccountNotFoundException($targetAccountId);
        }

        $sourceCurrency = $source->getCurrency();
        $sourceAmount = $this->currencyConverter->convert($amount, $currency, $sourceCurrency, true);
        if (is_null($sourceAmount)) {
            throw new TransactionException($sourceCurrency.' to '.$currency.' exchange is not supported');
        }

        return (new Transaction())
            ->setSource($source)
            ->setTarget($target)
            ->setTargetCurrency($currency)
            ->setTargetAmount($amount)
            ->setSourceCurrency($sourceCurrency)
            ->setSourceAmount($sourceAmount)
        ;
    }
}
