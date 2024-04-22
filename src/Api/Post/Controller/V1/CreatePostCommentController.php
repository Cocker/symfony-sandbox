<?php

declare(strict_types=1);

namespace App\Api\Post\Controller\V1;

use App\Api\Post\DTO\V1\CreatePostCommentDTO;
use App\Api\Post\Orchestrator\V1\PostCommentOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CreatePostCommentController extends AbstractController
{
    #[Route(
        path: '/posts/{postUlid}/comments',
        name: 'post.comments.create',
        requirements: ['postUlid' => Requirement::ULID],
        methods: ['POST'],
    )]
    public function __invoke(
        string $postUlid,
        Request $request,
        PostCommentOrchestrator $postCommentOrchestrator,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $postComment = $postCommentOrchestrator->create($postUlid, CreatePostCommentDTO::fromRequest($request));

        return $this->json(
            $normalizer->normalize($postComment, 'json', ['groups' => ['v1_comment', 'v1_metadata', 'timestamps']]),
            Response::HTTP_CREATED,
        );
    }
}
