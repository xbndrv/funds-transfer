<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/client-accounts', name: 'app_client_accounts')]
class ClientAccountsListController extends AbstractController
{
    use ErrorTrait;

    public function __construct(
        private readonly ClientRepository $repository
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $clientId = $request->query->get('client');
        if (is_null($clientId)) {
            return $this->createErrorResponse('No client id provided. Set \'client\' GET parameter.');
        }

        $client = $this->repository->find($clientId);
        if (is_null($client)) {
            return $this->createErrorResponse('Client #'.$clientId.' not found');
        }

        $accounts = [];
        foreach ($client->getAccounts() as $account) {
            $accounts[$account->getId()] = $account->getCurrency();
        }

        return new JsonResponse([
            'success' => true,
            'client' => [
                'id' => $client->getId(),
                'name' => $client->getName(),
            ],
            'accounts' => $accounts,
        ]);
    }
}
