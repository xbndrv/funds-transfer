<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/', name: 'app_index')]
class IndexController extends AbstractController
{
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    public function __invoke(): Response
    {
        return new JsonResponse([
            'success' => true,
            'endpoints' => [
                [
                    'uri' => $this->router->generate('app_index'),
                    'method' => 'GET',
                    'description' => 'API endpoints list',
                ],
                [
                    'uri' => $this->router->generate('app_clients'),
                    'method' => 'GET',
                    'description' => 'Clients list',
                    'parameters' => [
                        'offset' => 'number, 0 by default',
                        'limit' => 'number, '.ClientsListController::DEFAULT_LIMIT.' by default',
                    ],
                ],
                [
                    'uri' => $this->router->generate('app_client_accounts'),
                    'method' => 'GET',
                    'description' => 'Client\'s accounts list',
                    'parameters' => [
                        'client' => 'Client ID',
                    ],
                ],
                [
                    'uri' => $this->router->generate('app_account_transactions'),
                    'method' => 'GET',
                    'description' => 'Account transactions, both incoming and outgoing',
                    'parameters' => [
                        'account' => 'Account ID',
                        'offset' => 'number, 0 by default',
                        'limit' => 'number, '.AccountTransactionsListController::DEFAULT_LIMIT.' by default',
                    ],
                ],
                [
                    'uri' => $this->router->generate('app_transaction'),
                    'method' => 'POST',
                    'description' => 'Perform transaction',
                    'parameters' => [
                        'from' => 'Source account ID',
                        'to' => 'Target account ID',
                        'amount' => 'Transaction amount in cents (x100), integer',
                        'currency' => '3-letters currency code, f.e USD or EUR. Must match the target account currency.',
                    ],
                ],
            ],
        ]);
    }
}
