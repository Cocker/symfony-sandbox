<?php

declare(strict_types=1);

namespace App\Api\Post\Orchestrator\V1;

use App\Api\Post\DTO\V1\CreatePostDTO;
use App\Api\Post\DTO\V1\GetPostsDTO;
use App\Api\Post\DTO\V1\UpdatePostDTO;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Post;
use App\Api\Post\Exception\PostNotDraftException;
use App\Api\Post\Exception\PostNotPendingException;
use App\Api\Post\Service\V1\PostService;
use App\Api\Post\Voter\PostVoter;
use App\Api\User\Entity\User;
use App\Api\User\Service\V1\UserService;
use App\Api\User\Voter\UserVoter;
use App\Exception\AccessNotGrantedException;
use App\Exception\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

        if ($post === null || ! $this->authorizationChecker->isGranted(PostVoter::GET, $post)) {
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

    public function update(string $ulid, UpdatePostDTO $updatePostDTO): Post
    {
        $post = $this->postService->getByUlid($ulid);
        if ($post === null || ! $this->authorizationChecker->isGranted(PostVoter::UPDATE, $post)) {
            throw EntityNotFoundException::new(Post::class, $ulid);
        }

        if ($post->getStatus() !== PostStatus::DRAFT) {
            throw PostNotDraftException::new();
        }

        return $this->postService->update($post, $updatePostDTO);
    }

    /**
     * @param GetPostsDTO $getPostsDTO
     * @return Paginator<Post>
     */
    public function getAllPaginated(GetPostsDTO $getPostsDTO): Paginator
    {
        if (! $this->authorizationChecker->isGranted(PostVoter::GET_ANY, Post::class)) {
            throw AccessNotGrantedException::new();
        }

        return $this->postService->getAllPaginated($getPostsDTO);
    }

    /**
     * @param string $userUlid
     * @param GetPostsDTO $getPostsDTO
     * @return Paginator<Post>
     */
    public function getByUserPaginated(string $userUlid, GetPostsDTO $getPostsDTO): Paginator
    {
        $user = $this->userService->getByUlid($userUlid);

        if ($user === null || ! $this->authorizationChecker->isGranted(UserVoter::VIEW, $user)) {
            throw EntityNotFoundException::new(User::class, $userUlid);
        }

        return $this->postService->getByUserPaginated($user, $getPostsDTO);
    }
}
