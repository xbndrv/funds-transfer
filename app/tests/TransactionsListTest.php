<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransactionsListTest extends WebTestCase
{
    use TestTrait;

    public function testNoAccountProvidedError(): void
    {
        $httpClient = static::createClient();
        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions')
        );
        $this->assertErrorApiResponse($httpClient->getResponse());
    }

    public function testAccountNotExistError(): void
    {
        $httpClient = static::createClient();
        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions'),
            ['account' => 0]
        );
        $this->assertErrorApiResponse($httpClient->getResponse());
    }

    public function testAccountWithNoTransactions(): void
    {
        $httpClient = static::createClient();
        $client = $this->createClientEntity();
        $account = $this->createAccountEntity($client, 'XXX');
        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions'),
            ['account' => $account->getId()]
        );
        $response = $httpClient->getResponse();

        $this->assertResponseIsSuccessful();
        $json = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? 0);
        $this->assertEquals($account->getId(), $json['account']['id'] ?? 'undefined');
        $this->assertEquals('XXX', $json['account']['currency'] ?? 'undefined');
        $this->assertEquals($client->getId(), $json['client']['id'] ?? 'undefined');
        $this->assertCount(0, $json['transactions'] ?? ['xxx']);
    }

    public function testIncomingAndOutgoingTransactions(): void
    {
        $httpClient = static::createClient();

        $client = $this->createClientEntity();
        $account1 = $this->createAccountEntity($client, 'USD', 1000);
        $account2 = $this->createAccountEntity($client, 'USD', 1000);
        $this->createTransactionEntity($account1, $account2, 'USD', 1000);
        $this->createTransactionEntity($account2, $account1, 'USD', 1000);

        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions'),
            ['account' => $account1->getId()]
        );

        $json = $this->assertSuccessApiResponseAndReturnJson($httpClient->getResponse());
        $this->assertEquals($account1->getId(), $json['account']['id'] ?? 'undefined');
        $this->assertEquals('USD', $json['account']['currency'] ?? 'undefined');
        $this->assertEquals($client->getId(), $json['client']['id'] ?? 'undefined');
        $this->assertCount(2, $json['transactions'] ?? []);
        $this->assertEquals(
            $account2->getId(),
            $json['transactions'][0]['account'] ?? 'undefined'
        );
        $this->assertEquals(
            $account2->getId(),
            $json['transactions'][1]['account'] ?? 'undefined'
        );
        $this->assertEquals(
            1000,
            $json['transactions'][0]['amount'] ?? 'undefined'
        );
        $this->assertEquals(
            -1000,
            $json['transactions'][1]['amount'] ?? 'undefined'
        );
    }

    public function testTransactionsPagination(): void
    {
        $httpClient = static::createClient();

        $client = $this->createClientEntity();
        $source = $this->createAccountEntity($client, 'USD', 100000000);
        $target = $this->createAccountEntity($client, 'USD', 0);
        for ($i = 100; $i <= 300; ++$i) {
            $this->createTransactionEntity($source, $target, 'USD', $i);
        }

        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions'),
            ['account' => $target->getId(), 'limit' => 5, 'offset' => 3]
        );

        $json = $this->assertSuccessApiResponseAndReturnJson($httpClient->getResponse());
        $this->assertCount(5, $json['transactions'] ?? []);
        $this->assertEquals(
            297,
            $json['transactions'][0]['amount'] ?? 'undefined'
        );
    }
}
