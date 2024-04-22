<?php

declare(strict_types=1);

namespace App\Api\Post\Controller\V1;

use App\Api\Post\DTO\V1\GetPostsDTO;
use App\Api\Post\Orchestrator\V1\PostCommentOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GetPostCommentsByUserController extends AbstractController
{
    #[Route(
        path: '/users/{userUlid}/post-comments',
        name: 'post.comments.get-by-user',
        requirements: ['userUlid' => Requirement::ULID],
        methods: ['GET'],
    )]
    public function __invoke(
        string $userUlid,
        Request $request,
        PostCommentOrchestrator $postCommentOrchestrator,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $postCommentsPaginator = $postCommentOrchestrator->getByUserPaginated(
            $userUlid,
            GetPostsDTO::fromRequest($request)
        );

        return $this->json(
            $normalizer->normalize(
                $postCommentsPaginator,
                'json',
                ['groups' => ['v1_comment', 'v1_metadata', 'timestamps']]
            ),
            Response::HTTP_OK,
        );
    }
}
