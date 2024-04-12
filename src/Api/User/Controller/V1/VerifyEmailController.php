<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\VerifyEmailDTO;
use App\Api\User\Orchestrator\V1\EmailVerificationOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VerifyEmailController extends AbstractController
{
    #[Route(path: '/email/verify', name: 'user.email.verify', methods: ['POST'])]
    public function __invoke(Request $request, EmailVerificationOrchestrator $emailOrchestrator): JsonResponse
    {
        $emailOrchestrator->verify(VerifyEmailDTO::fromRequest($request));

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
