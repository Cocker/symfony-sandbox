<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\Orchestrator\V1\AuthOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GetAuthenticatedUserController extends AbstractController
{
    #[Route(path: '/auth/me', name: 'auth.get-user', methods: ['GET'])]
    public function __invoke(AuthOrchestrator $authOrchestrator, NormalizerInterface $normalizer,): JsonResponse
    {
        $user = $authOrchestrator->getUser();

        return $this->json(
            $normalizer->normalize($user, 'json', ['groups' => ['v1_personal', 'v1_metadata', 'timestamps']])
        );
    }
}
