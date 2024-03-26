<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\Service\V1\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GetAuthenticatedUserController extends AbstractController
{
    #[Route(path: '/auth/me', name: 'auth.me', methods: ['GET'])]
    public function __invoke(AuthService $authService, NormalizerInterface $normalizer,): JsonResponse
    {
        $user = $authService->getUser();

        return $this->json(
            $normalizer->normalize($user, 'json', ['groups' => ['v1_personal', 'v1_metadata', 'timestamps']])
        );
    }
}
