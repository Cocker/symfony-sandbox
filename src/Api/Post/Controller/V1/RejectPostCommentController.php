<?php

declare(strict_types=1);

namespace App\Api\Post\Controller\V1;

use App\Api\Post\Orchestrator\V1\PostCommentOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class RejectPostCommentController extends AbstractController
{
    #[Route(
        path: '/post-comments/{ulid}/reject',
        name: 'post-comments.reject',
        requirements: ['ulid' => Requirement::ULID],
        methods: ['POST'],
    )]
    public function __invoke(
        string $ulid,
        PostCommentOrchestrator $postCommentOrchestrator,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $postComment = $postCommentOrchestrator->reject($ulid);

        return $this->json(
            $normalizer->normalize($postComment,'json', ['groups' => ['v1_comment', 'v1_metadata', 'timestamps']]),
            Response::HTTP_OK,
        );
    }
}
