<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\CreateUserDTO;
use App\Api\User\Orchestrator\V1\UserOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CreateUserController extends AbstractController
{
    #[Route(path: '/users', name: 'users.create', methods: ['POST'])]
    public function __invoke(
        Request $request,
        NormalizerInterface $normalizer,
        UserOrchestrator $userOrchestrator,
    ): JsonResponse {
        $user = $userOrchestrator->create(CreateUserDTO::fromRequest($request));

        return $this->json(
            $normalizer->normalize($user, 'json', ['groups' => ['v1_personal', 'v1_metadata', 'timestamps']]),
            Response::HTTP_CREATED
        );
    }
}
