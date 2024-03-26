<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\UpdateUserDTO;
use App\Api\User\Service\V1\AuthService;
use App\Api\User\Service\V1\UserService;
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
        AuthService $authService,
        UserService $userService
    ): JsonResponse {
        $user = $userService->update($authService->getUser(), UpdateUserDTO::fromRequest($request));

        return $this->json(
            $normalizer->normalize($user, 'json', ['groups' => ['v1_personal', 'v1_metadata', 'timestamps']]),
            Response::HTTP_OK,
        );
    }
}
