<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Exception\TransactionException;
use Doctrine\ORM\EntityManagerInterface;

class TransactionPerformer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function perform(Transaction $transaction): void
    {
        $source = $transaction->getSource();
        $target = $transaction->getTarget();
        if (is_null($source)) {
            throw new TransactionException('No source account set');
        }
        if (is_null($target)) {
            throw new TransactionException('No target account set');
        }
        if ($source->getId() == $target->getId()) {
            throw new TransactionException('Transactions to the same account are forbidden');
        }
        if ($transaction->getSourceAmount() <= 0) {
            throw new TransactionException('Amount must be positive');
        }
        if ($transaction->getTargetAmount() <= 0) {
            throw new TransactionException('Amount must be positive');
        }
        if ($transaction->getSourceCurrency() !== $source->getCurrency()) {
            throw new TransactionException('Transaction currency doesn\'t match source account');
        }
        if ($transaction->getTargetCurrency() !== $target->getCurrency()) {
            throw new TransactionException('Transaction currency doesn\'t match target account');
        }
        if ($source->getAmount() < $transaction->getSourceAmount()) {
            throw new TransactionException('Not enough money on the source account');
        }

        $source->setAmount($source->getAmount() - $transaction->getSourceAmount());
        $target->setAmount($target->getAmount() + $transaction->getTargetAmount());

        $this->entityManager->persist($transaction);
        $this->entityManager->persist($source);
        $this->entityManager->persist($target);

        $this->entityManager->flush();
    }
}
