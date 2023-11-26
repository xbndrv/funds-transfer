<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransactionsListTest extends WebTestCase
{
    use \App\Tests\TestTrait;

    public function testNoAccountError(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions')
        );
        $response = $client->getResponse();

        $this->assertResponseIsSuccessful();
        $json = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? 'unset');
        $this->assertArrayHasKey('message', $json);
    }

    public function testAccountNotExistError(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions'),
            ['account' => 0]
        );
        $response = $client->getResponse();

        $this->assertResponseIsSuccessful();
        $json = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? 'unset');
        $this->assertArrayHasKey('message', $json);
    }

    public function testEmptyAccount(): void
    {
        $client = static::createClient();
        $clientEntity = $this->createClientEntity();
        $account = $this->createAccountEntity($clientEntity, 'XXX');
        $client->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions'),
            ['account' => $account->getId()]
        );
        $response = $client->getResponse();

        $this->assertResponseIsSuccessful();
        $json = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? 0);
        $this->assertEquals($account->getId(), $json['account']['id'] ?? 'undefined');
        $this->assertEquals('XXX', $json['account']['currency'] ?? 'undefined');
        $this->assertEquals($clientEntity->getId(), $json['client']['id'] ?? 'undefined');
        $this->assertCount(0, $json['transactions'] ?? ['xxx']);
    }

    public function testClientAccountsList(): void
    {
        $client = static::createClient();
        $clientEntity = $this->createClientEntity();
        $account1 = $this->createAccountEntity($clientEntity, 'USD', 1000);
        $account2 = $this->createAccountEntity($clientEntity, 'USD', 1000);
        $transaction1 = $this->createTransactionEntity($account1, $account2, 'USD', 1000);
        $transaction2 = $this->createTransactionEntity($account2, $account1, 'USD', 1000);

        $client->request(
            'GET',
            $this->getRouter()->generate('app_account_transactions'),
            ['account' => $account1->getId()]
        );
        $response = $client->getResponse();

        $this->assertResponseIsSuccessful();
        $json = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? 0);
        $this->assertEquals($account1->getId(), $json['account']['id'] ?? 'undefined');
        $this->assertEquals('USD', $json['account']['currency'] ?? 'undefined');
        $this->assertEquals($clientEntity->getId(), $json['client']['id'] ?? 'undefined');
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
}
