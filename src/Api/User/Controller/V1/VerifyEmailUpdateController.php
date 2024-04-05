<?php

declare(strict_types=1);

namespace App\Api\User\Controller\V1;

use App\Api\User\DTO\V1\VerifyEmailUpdateDTO;
use App\Api\User\Orchestrator\V1\UpdateEmailOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class VerifyEmailUpdateController extends AbstractController
{
    #[Route(path: '/email/verify-update', name: 'email.verify-update', methods: ['POST'])]
    public function __invoke(
        Request $request,
        UpdateEmailOrchestrator $updateEmailOrchestrator,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $user = $updateEmailOrchestrator->update(VerifyEmailUpdateDTO::fromRequest($request));

        return $this->json(
            $normalizer->normalize($user, 'json', ['groups' => ['v1_personal', 'v1_metadata', 'timestamps']]),
            Response::HTTP_OK,
        );
    }
}
