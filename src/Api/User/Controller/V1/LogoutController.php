<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class LogoutController extends AbstractController
{
    #[Route(path: '/auth/logout', name: 'auth.logout', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        return $this->json([]);
    }
}
