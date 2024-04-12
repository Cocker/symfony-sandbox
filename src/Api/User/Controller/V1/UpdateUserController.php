<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\UpdateUserDTO;
use App\Api\User\Orchestrator\V1\UserOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UpdateUserController extends AbstractController
{
    #[Route(
        path: '/users/{ulid}',
        name: 'user.update',
        requirements: ['ulid' =>Requirement::ULID],
        methods: ['PUT'],
    )]
    public function __invoke(
        string $ulid,
        Request $request,
        NormalizerInterface $normalizer,
        UserOrchestrator $userOrchestrator,
    ): JsonResponse {
        $user = $userOrchestrator->update($ulid, UpdateUserDTO::fromRequest($request));

        return $this->json(
            $normalizer->normalize($user, 'json', ['groups' => ['v1_personal', 'v1_metadata', 'timestamps']]),
            Response::HTTP_OK,
        );
    }
}
