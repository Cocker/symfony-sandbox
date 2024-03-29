<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\SendEmailVerificationEmailDTO;
use App\Api\User\Orchestrator\V1\EmailOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SendEmailVerificationEmailController extends AbstractController
{
    #[Route(path: '/email/send-verification', name: 'email.verify.send', methods: ['POST'])]
    public function __invoke(Request $request, EmailOrchestrator $emailOrchestrator): JsonResponse
    {
        $emailOrchestrator->sendVerificationEmail(SendEmailVerificationEmailDTO::fromRequest($request));

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
