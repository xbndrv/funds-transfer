<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/', name: 'app_index')]
class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        return new JsonResponse([
            'success' => true,
            'test' => 646,
        ]);
    }
}
