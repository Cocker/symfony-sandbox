<?php

declare(strict_types=1);

namespace App\Api\Post\Orchestrator\V1;

use App\Api\Post\DTO\V1\CreatePostDTO;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Post;
use App\Api\Post\Exception\PostNotDraftException;
use App\Api\Post\Exception\PostNotPendingException;
use App\Api\Post\Service\V1\PostService;
use App\Api\Post\Voter\PostVoter;
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

    public function getByUlid(string $ulid): Post
    {
        $post = $this->postService->getByUlid($ulid);

        if ($post === null || ! $this->authorizationChecker->isGranted(PostVoter::VIEW, $post)) {
            throw EntityNotFoundException::new(Post::class, $ulid);
        }

        return $post;
    }

    public function complete(string $ulid): Post
    {
        $post = $this->postService->getByUlid($ulid);
        if ($post === null || ! $this->authorizationChecker->isGranted(PostVoter::COMPLETE, $post)) {
            throw EntityNotFoundException::new(Post::class, $ulid);
        }

        if ($post->getStatus() !== PostStatus::DRAFT) {
            throw PostNotDraftException::new();
        }

        return $this->postService->complete($post);
    }

    public function publish(string $ulid): Post
    {
        $post = $this->postService->getByUlid($ulid);
        if ($post === null || ! $this->authorizationChecker->isGranted(PostVoter::PUBLISH, $post)) {
            throw EntityNotFoundException::new(Post::class, $ulid);
        }

        if ($post->getStatus() !== PostStatus::PENDING) {
            throw PostNotPendingException::new();
        }

        return $this->postService->publish($post);
    }

    public function reject(string $ulid): Post
    {
        $post = $this->postService->getByUlid($ulid);
        if ($post === null || ! $this->authorizationChecker->isGranted(PostVoter::REJECT, $post)) {
            throw EntityNotFoundException::new(Post::class, $ulid);
        }

        if ($post->getStatus() !== PostStatus::PENDING) {
            throw PostNotPendingException::new();
        }

        return $this->postService->reject($post);
    }
}
