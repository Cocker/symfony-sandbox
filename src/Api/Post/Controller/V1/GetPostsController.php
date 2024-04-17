<?php

declare(strict_types=1);

namespace App\Api\Post\Controller\V1;

use App\Api\Post\DTO\V1\GetPostsDTO;
use App\Api\Post\Orchestrator\V1\PostOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GetPostsController extends AbstractController
{
    #[Route(
        path: '/posts',
        name: 'post.all',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        PostOrchestrator $postOrchestrator,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $postsPaginator = $postOrchestrator->getAllPaginated(GetPostsDTO::fromRequest($request));

        return $this->json(
            $normalizer->normalize($postsPaginator, 'json', ['groups' => ['v1_post', 'v1_metadata', 'timestamps']]),
            Response::HTTP_OK,
        );
    }
}
