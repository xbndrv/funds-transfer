<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/clients', name: 'app_clients')]
class ClientsListController extends AbstractController
{
    public const DEFAULT_LIMIT = 50;

    public function __construct(
        private readonly ClientRepository $repository
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $offset = $request->query->getInt('offset');
        $limit = $request->query->getInt('limit', self::DEFAULT_LIMIT);
        $total = $this->repository->count([]);
        $ret = [];
        foreach ($this->repository->findBy([], ['id' => 'ASC'], $limit, $offset) as $client) {
            $ret[$client->getId()] = $client->getName();
        }

        return new JsonResponse([
            'success' => true,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'clients' => $ret,
        ]);
    }
}
