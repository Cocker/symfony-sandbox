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
use Symfony\Component\Routing\Requirement\Requirement;

class RequestEmailUpdateController extends AbstractController
{
    #[Route(
        path: 'users/{ulid}/email/request-update',
        name: 'user.email.request-update',
        requirements: ['ulid' => Requirement::ULID],
        methods: ['POST']
    )]
    public function __invoke(
        string $ulid,
        Request $request,
        UpdateEmailOrchestrator $updateEmailOrchestrator,
    ): JsonResponse
    {
        $dto = RequestEmailUpdateDTO::fromRequest($request);
        $updateEmailOrchestrator->requestUpdate($ulid, $dto);

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
