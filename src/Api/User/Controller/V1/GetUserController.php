<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\Orchestrator\V1\UserOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GetUserController extends AbstractController
{
    #[Route(
        path: '/users/{ulid}',
        name: 'user.get',
        requirements: ['ulid' => Requirement::ULID],
        methods: ['GET']
    )]
    public function __invoke(
        string $ulid,
        UserOrchestrator $userOrchestrator,
        NormalizerInterface $normalizer
    ): JsonResponse {
        $user = $userOrchestrator->getByUlid($ulid);

        return $this->json(
            $normalizer->normalize($user, 'json', ['groups' => ['v1_personal', 'v1_metadata', 'timestamps']])
        );
    }
}
