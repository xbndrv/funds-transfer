<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ErrorTrait
{
    public function createErrorResponse(string $error): Response
    {
        return new JsonResponse([
            'success' => false,
            'message' => $error,
        ]);
    }
}
