<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\UpdateUserDTO;
use App\Api\User\Orchestrator\V1\AuthOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UpdateAuthenticatedUserController extends AbstractController
{
    #[Route(path: '/auth/me', name: 'auth.update-user', methods: ['PUT'])]
    public function __invoke(
        Request $request,
        NormalizerInterface $normalizer,
        AuthOrchestrator $authOrchestrator,
    ): JsonResponse {
        $user = $authOrchestrator->updateUser(UpdateUserDTO::fromRequest($request));

        return $this->json(
            $normalizer->normalize($user, 'json', ['groups' => ['v1_personal', 'v1_metadata', 'timestamps']]),
            Response::HTTP_OK,
        );
    }
}
