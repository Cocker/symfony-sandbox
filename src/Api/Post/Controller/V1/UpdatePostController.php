<?php

declare(strict_types=1);

namespace App\Api\Post\Controller\V1;

use App\Api\Post\DTO\V1\UpdatePostDTO;
use App\Api\Post\Orchestrator\V1\PostOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UpdatePostController extends AbstractController
{
    #[Route(
        path: '/posts/{ulid}',
        name: 'post.update',
        requirements: ['ulid' => Requirement::ULID],
        methods: ['PUT'],
    )]
    public function __invoke(
        string $ulid,
        Request $request,
        PostOrchestrator $postOrchestrator,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $post = $postOrchestrator->update($ulid, UpdatePostDTO::fromRequest($request));

        return $this->json(
            $normalizer->normalize($post, 'json', ['groups' => ['v1_post', 'v1_metadata', 'timestamps']]),
            Response::HTTP_OK,
        );
    }
}
