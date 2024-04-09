<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\ResetPasswordDTO;
use App\Api\User\Orchestrator\V1\PasswordOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends AbstractController
{
    #[Route(path: '/password/reset', name: 'password.reset', methods: ['POST'])]
    public function __invoke(Request $request, PasswordOrchestrator $passwordOrchestrator): JsonResponse
    {
        $passwordOrchestrator->reset(ResetPasswordDTO::fromRequest($request));

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
