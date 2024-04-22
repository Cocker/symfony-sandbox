<?php

declare(strict_types=1);

namespace App\Api\Post\Orchestrator\V1;

use App\Api\Post\DTO\V1\CreatePostCommentDTO;
use App\Api\Post\DTO\V1\GetPostsDTO;
use App\Api\Post\Entity\Enum\PostCommentStatus;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Post;
use App\Api\Post\Entity\PostComment;
use App\Api\Post\Exception\PostCommentNotPendingException;
use App\Api\Post\Service\V1\PostCommentService;
use App\Api\Post\Service\V1\PostService;
use App\Api\Post\Voter\PostCommentVoter;
use App\Api\Post\Voter\PostVoter;
use App\Api\User\Entity\User;
use App\Api\User\Service\V1\UserService;
use App\Api\User\Voter\UserVoter;
use App\Exception\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PostCommentOrchestrator
{
    public function __construct(
        protected readonly PostService $postService,
        protected readonly PostCommentService $postCommentService,
        protected readonly UserService $userService,
        protected readonly AuthorizationCheckerInterface $authorizationChecker,
        protected readonly Security $security,
    ) {
        //
    }

    public function create(string $postUlid, CreatePostCommentDTO $createPostCommentDTO): PostComment
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $post = $this->postService->getByUlid($postUlid);

        if ($user === null || $post === null || $post->getStatus() !== PostStatus::PUBLISHED) {
            throw EntityNotFoundException::new(Post::class, $postUlid);
        }

        return $this->postCommentService->create($user, $post, $createPostCommentDTO);
    }

    /**
     * @param string $postUlid
     * @param GetPostsDTO $getPostsDTO
     * @return Paginator<PostComment>
     */
    public function getByPostPaginated(string $postUlid, GetPostsDTO $getPostsDTO): Paginator
    {
        $post = $this->postService->getByUlid($postUlid);

        if ($post === null || ! $this->authorizationChecker->isGranted(PostVoter::GET, $post)) {
            throw EntityNotFoundException::new(Post::class, $postUlid);
        }

        return $this->postCommentService->findByPostPaginated($post, $getPostsDTO);
    }

    /**
     * @param string $userUlid
     * @param GetPostsDTO $getPostsDTO
     * @return Paginator<PostComment>
     */
    public function getByUserPaginated(string $userUlid, GetPostsDTO $getPostsDTO): Paginator
    {
        $user = $this->userService->getByUlid($userUlid);

        if ($user === null || ! $this->authorizationChecker->isGranted(UserVoter::GET, $user)) {
            throw EntityNotFoundException::new(User::class, $userUlid);
        }

        return $this->postCommentService->findByUserPaginated($user, $getPostsDTO);
    }

    public function approve(string $ulid): PostComment
    {
        $postComment = $this->postCommentService->getByUlid($ulid);

        if (
            $postComment === null
            || ! $this->authorizationChecker->isGranted(PostCommentVoter::APPROVE, $postComment)
        ) {
            throw EntityNotFoundException::new(PostComment::class, $ulid);
        }

        if ($postComment->getStatus() !== PostCommentStatus::PENDING) {
            throw PostCommentNotPendingException::new();
        }

        return $this->postCommentService->approve($postComment);
    }

    public function reject(string $ulid): PostComment
    {
        $postComment = $this->postCommentService->getByUlid($ulid);

        if (
            $postComment === null
            || ! $this->authorizationChecker->isGranted(PostCommentVoter::REJECT, $postComment)
        ) {
            throw EntityNotFoundException::new(PostComment::class, $ulid);
        }

        if ($postComment->getStatus() !== PostCommentStatus::PENDING) {
            throw PostCommentNotPendingException::new();
        }

        return $this->postCommentService->reject($postComment);
    }
}
