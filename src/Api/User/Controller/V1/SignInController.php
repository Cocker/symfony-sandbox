<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\SignInDTO;
use App\Api\User\Service\V1\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SignInController extends AbstractController
{
    #[Route(path: '/sign-in', name: 'sign-in', methods: ['POST'])]
    public function __invoke(Request $request, AuthService $authService): JsonResponse
    {
        return $this->json(['token' => $authService->login(SignInDTO::fromRequest($request))]);
    }
}
