<?php

declare(strict_types=1);

namespace App\Api\Post\Orchestrator\V1;

use App\Api\Post\DTO\V1\CreatePostDTO;
use App\Api\Post\Entity\Post;
use App\Api\Post\Service\V1\PostService;
use App\Api\User\Entity\User;
use App\Api\User\Service\V1\UserService;
use App\Api\User\Voter\UserVoter;
use App\Exception\EntityNotFoundException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PostOrchestrator
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly PostService $postService,
        protected readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
        //
    }

    public function create(string $userUlid, CreatePostDTO $createPostDTO): Post
    {
        $user = $this->userService->getByUlid($userUlid);

        if ($user === null || ! $this->authorizationChecker->isGranted(UserVoter::UPDATE, $user)) {
            throw EntityNotFoundException::new(User::class, $userUlid);
        }

        return $this->postService->create($user, $createPostDTO);
    }
}
