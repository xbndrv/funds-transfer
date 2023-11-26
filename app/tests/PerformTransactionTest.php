<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PerformTransactionTest extends WebTestCase
{
    use TestTrait;

    public function testParameterLackError(): void
    {
        $httpClient = static::createClient();

        $client = $this->createClientEntity();
        $source = $this->createAccountEntity($client, 'USD', 1000);
        $target = $this->createAccountEntity($client, 'USD', 1000);

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'to' => $target->getId(),
                'amount' => 100,
                'currency' => 'USD',
            ]
        );

        $this->assertErrorApiResponse($httpClient->getResponse());

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $source->getId(),
                'amount' => 100,
                'currency' => 'USD',
            ]
        );

        $this->assertErrorApiResponse($httpClient->getResponse());

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $source->getId(),
                'to' => $target->getId(),
                'currency' => 'USD',
            ]
        );

        $this->assertErrorApiResponse($httpClient->getResponse());

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $source->getId(),
                'to' => $target->getId(),
                'amount' => 100,
            ]
        );

        $this->assertErrorApiResponse($httpClient->getResponse());
    }

    public function testAccountNotFoundError(): void
    {
        $httpClient = static::createClient();

        $client = $this->createClientEntity();
        $account = $this->createAccountEntity($client, 'USD', 1000);
        $deletedAccount = $this->createAccountEntity($client, 'USD', 1000);
        $deletedAccountId = $deletedAccount->getId();
        $this->getEntityManager()->remove($deletedAccount);
        $this->getEntityManager()->flush();

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $account->getId(),
                'to' => $deletedAccountId,
                'amount' => 100,
                'currency' => 'USD',
            ]
        );

        $this->assertErrorApiResponse($httpClient->getResponse());

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $deletedAccountId,
                'to' => $account->getId(),
                'amount' => 100,
                'currency' => 'USD',
            ]
        );

        $this->assertErrorApiResponse($httpClient->getResponse());
    }

    public function testNotEnoughFunds(): void
    {
        $httpClient = static::createClient();

        $client = $this->createClientEntity();
        $source = $this->createAccountEntity($client, 'XXX', 1000);
        $target = $this->createAccountEntity($client, 'XXX', 1000);

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $source->getId(),
                'to' => $target->getId(),
                'amount' => 1001,
                'currency' => 'XXX',
            ]
        );

        $this->assertErrorApiResponse($httpClient->getResponse());

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $source->getId(),
                'to' => $target->getId(),
                'amount' => 1000,
                'currency' => 'XXX',
            ]
        );

        $this->assertSuccessApiResponseAndReturnJson($httpClient->getResponse());
    }

    public function testCurrencyDoesntMatchTargetAccount(): void
    {
        $httpClient = static::createClient();

        $client = $this->createClientEntity();
        $source = $this->createAccountEntity($client, 'EUR', 1000);
        $target = $this->createAccountEntity($client, 'XXX', 1000);

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $source->getId(),
                'to' => $target->getId(),
                'amount' => 100,
                'currency' => 'EUR',
            ]
        );

        $this->assertErrorApiResponse($httpClient->getResponse());
    }

    public function testConversionToUnknownCurrency(): void
    {
        $httpClient = static::createClient();

        $client = $this->createClientEntity();
        $source = $this->createAccountEntity($client, 'EUR', 100000000);
        $target = $this->createAccountEntity($client, 'OOO', 100);

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $source->getId(),
                'to' => $target->getId(),
                'amount' => 100,
                'currency' => 'OOO',
            ]
        );

        $this->assertErrorApiResponse($httpClient->getResponse());
    }

    public function testSameCurrencyTransfer(): void
    {
        $amount = rand(100, 900);

        $httpClient = static::createClient();

        $client = $this->createClientEntity();
        $source = $this->createAccountEntity($client, 'XXX', 1000);
        $target = $this->createAccountEntity($client, 'XXX', 1000);

        // performing transaction

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $source->getId(),
                'to' => $target->getId(),
                'amount' => $amount,
                'currency' => 'XXX',
            ]
        );

        $this->assertSuccessApiResponseAndReturnJson($httpClient->getResponse());

        // Checking amounts

        $this->assertEquals(1000 - $amount, $source->getAmount());
        $this->assertEquals(1000 + $amount, $target->getAmount());

        // Transaction appeared in transaction list

        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions'),
            ['account' => $target->getId()]
        );

        $json = $this->assertSuccessApiResponseAndReturnJson($httpClient->getResponse());
        $this->assertEquals(
            $source->getId(),
            $json['transactions'][0]['account'] ?? 'undefined'
        );
        $this->assertEquals(
            $amount,
            $json['transactions'][0]['amount'] ?? 'undefined'
        );
    }

    public function testEurToGbpConversion(): void
    {
        $httpClient = static::createClient();

        $gbpAmount = 1000;
        $eurAmount = $this->getCurrencyConverter()->convert($gbpAmount, 'GBP', 'EUR', true);

        $client = $this->createClientEntity();
        $source = $this->createAccountEntity($client, 'EUR', 1000 + $eurAmount);
        $target = $this->createAccountEntity($client, 'GBP', 1000);

        // perform transaction

        $httpClient->request(
            'POST',
            $this->getRouter()->generate('app_transaction'),
            [
                'from' => $source->getId(),
                'to' => $target->getId(),
                'amount' => $gbpAmount,
                'currency' => 'GBP',
            ]
        );

        $this->assertSuccessApiResponseAndReturnJson($httpClient->getResponse());

        // Checking amounts

        $this->assertEquals(1000, $source->getAmount());
        $this->assertEquals(1000 + $gbpAmount, $target->getAmount());

        // Transaction appeared in transaction list

        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions'),
            ['account' => $target->getId()]
        );

        $json = $this->assertSuccessApiResponseAndReturnJson($httpClient->getResponse());
        $this->assertEquals(
            $source->getId(),
            $json['transactions'][0]['account'] ?? 'undefined'
        );
        $this->assertEquals(
            $gbpAmount,
            $json['transactions'][0]['amount'] ?? 'undefined'
        );
    }
}
