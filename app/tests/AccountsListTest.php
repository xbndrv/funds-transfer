<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountsListTest extends WebTestCase
{
    use TestTrait;

    public function testNoClientProvidedError(): void
    {
        $httpClient = static::createClient();
        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_client_accounts')
        );
        $this->assertErrorApiResponse($httpClient->getResponse());
    }

    public function testClientNotExistError(): void
    {
        $httpClient = static::createClient();
        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_client_accounts'),
            ['client' => 0]
        );
        $this->assertErrorApiResponse($httpClient->getResponse());
    }

    public function testCorrectClientAccountsList(): void
    {
        $httpClient = static::createClient();
        $client = $this->createClientEntity();
        $usdAccount = $this->createAccountEntity($client, 'USD', 1000);
        $eurAccount = $this->createAccountEntity($client, 'EUR', 1000);

        $httpClient->request(
            'GET',
            $this->getRouter()->generate('app_client_accounts'),
            ['client' => $client->getId()]
        );

        $json = $this->assertSuccessApiResponseAndReturnJson($httpClient->getResponse());
        $this->assertEquals($client->getId(), $json['client']['id'] ?? 'undefined');
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
