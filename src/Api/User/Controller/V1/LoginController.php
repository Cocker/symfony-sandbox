<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\SignInDTO;
use App\Api\User\Orchestrator\V1\AuthOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    #[Route(path: '/auth/login', name: 'user.auth.login', methods: ['POST'])]
    public function __invoke(Request $request, AuthOrchestrator $authOrchestrator): JsonResponse
    {
        return $this->json(['token' => $authOrchestrator->login(SignInDTO::fromRequest($request))]);
    }
}
