<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountsListTest extends WebTestCase
{
    use TestTrait;

    public function testNoClientError(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            $this->getRouter()->generate('app_client_accounts')
        );
        $response = $client->getResponse();

        $this->assertResponseIsSuccessful();
        $json = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? 'unset');
        $this->assertArrayHasKey('message', $json);
    }

    public function testClientNotExistError(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            $this->getRouter()->generate('app_client_accounts'),
            ['client' => 0]
        );
        $response = $client->getResponse();

        $this->assertResponseIsSuccessful();
        $json = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? 'unset');
        $this->assertArrayHasKey('message', $json);
    }

    public function testClientAccountsList(): void
    {
        $client = static::createClient();
        $clientEntity = $this->createClientEntity();
        $usdAccount = $this->createAccountEntity($clientEntity, 'USD', 1000);
        $eurAccount = $this->createAccountEntity($clientEntity, 'EUR', 1000);

        $client->request(
            'GET',
            $this->getRouter()->generate('app_client_accounts'),
            ['client' => $clientEntity->getId()]
        );
        $response = $client->getResponse();

        $this->assertResponseIsSuccessful();
        $json = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? 0);
        $this->assertEquals($clientEntity->getId(), $json['client']['id'] ?? 'undefined');
        $this->assertCount(2, $json['accounts'] ?? []);
        $this->assertEquals(
            'EUR',
            $json['accounts'][$eurAccount->getId()] ?? 'undefined'
        );
        $this->assertEquals(
            'USD',
            $json['accounts'][$usdAccount->getId()] ?? 'undefined'
        );
    }
}
