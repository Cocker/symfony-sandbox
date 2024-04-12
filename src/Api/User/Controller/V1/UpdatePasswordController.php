<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\UpdatePasswordDTO;
use App\Api\User\Orchestrator\V1\PasswordOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class UpdatePasswordController extends AbstractController
{
    #[Route(
        path: 'users/{ulid}/password',
        name: 'user.password.update',
        requirements: ['ulid' => Requirement::ULID],
        methods: ['PUT'],
    )]
    public function __invoke(string $ulid, Request $request, PasswordOrchestrator $passwordOrchestrator): JsonResponse
    {
        $passwordOrchestrator->update($ulid, UpdatePasswordDTO::fromRequest($request));

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
