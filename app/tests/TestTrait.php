<?php

namespace App\Tests;

use App\Entity\Account;
use App\Entity\Client;
use App\Entity\Transaction;
use App\Service\TransactionFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

trait TestTrait
{
    protected function getTransactionFactory(): TransactionFactory
    {
        $transactionFactory = self::getContainer()->get(TransactionFactory::class);
        if (!$transactionFactory instanceof TransactionFactory) {
            throw new \Exception('No TransactionFactory');
        }

        return $transactionFactory;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \Exception('No EntityManager');
        }

        return $entityManager;
    }

    protected function getRouter(): RouterInterface
    {
        $router = self::getContainer()->get(RouterInterface::class);
        if (!$router instanceof RouterInterface) {
            throw new \Exception('No Router');
        }

        return $router;
    }

    protected function createClientEntity(): Client
    {
        static $clientNo;
        if (!isset($clientNo)) {
            $clientNo = rand(0, 100000);
        }
        $client = new Client();
        $client->setName('Client '.$clientNo);
        ++$clientNo;
        $this->getEntityManager()->persist($client);
        $this->getEntityManager()->flush();

        return $client;
    }

    protected function createAccountEntity(CLient $client, string $currency = 'EUR', int $amount = 0): Account
    {
        $account = new Account();
        $account->setAmount($amount);
        $account->setCurrency($currency);
        $client->addAccount($account);
        $this->getEntityManager()->persist($account);
        $this->getEntityManager()->flush();

        return $account;
    }

    protected function createTransactionEntity(Account $source, Account $target, string $currency = 'USD', int $amount = 100): Transaction
    {
        $transaction = new Transaction();
        $transaction->setSourceAmount($amount);
        $transaction->setTargetAmount($amount);
        $transaction->setSourceCurrency($currency);
        $transaction->setTargetCurrency($currency);
        $source->addOutgoingTransaction($transaction);
        $target->addIncomingTransaction($transaction);
        $this->getEntityManager()->persist($transaction);
        $this->getEntityManager()->flush();

        return $transaction;
    }
}
