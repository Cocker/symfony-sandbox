<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\RequestEmailUpdateDTO;
use App\Api\User\Orchestrator\V1\UpdateEmailOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RequestEmailUpdateController extends AbstractController
{
    #[Route(path: '/email/request-update', name: 'email.request-update', methods: ['POST'])]
    public function __invoke(Request $request, UpdateEmailOrchestrator $updateEmailOrchestrator): JsonResponse
    {
        $updateEmailOrchestrator->requestUpdate(RequestEmailUpdateDTO::fromRequest($request));

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
