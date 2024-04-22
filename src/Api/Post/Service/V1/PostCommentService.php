<?php

declare(strict_types=1);

namespace App\Api\Post\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\Post\DTO\V1\CreatePostCommentDTO;
use App\Api\Post\DTO\V1\GetPostsDTO;
use App\Api\Post\Entity\Enum\PostCommentStatus;
use App\Api\Post\Entity\Post;
use App\Api\Post\Entity\PostComment;
use App\Api\Post\Repository\V1\PostCommentRepository;
use App\Api\User\Entity\User;
use App\Util\PaginationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PostCommentService
{
    use PaginationTrait;

    public const int RESULTS_PER_PAGE = 10;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
        protected readonly PostCommentRepository $postCommentRepository,
    ) {
        //
    }

    public function create(User $user, Post $post, CreatePostCommentDTO $createPostCommentDTO): PostComment
    {
        $postComment = new PostComment();

        $postComment
            ->setPost($post)
            ->setAuthor($user)
            ->setContent($createPostCommentDTO->content)
        ;

        $this->validator->validate($postComment);

        $this->entityManager->persist($postComment);
        $this->entityManager->flush();

        return $postComment;
    }

    /**
     * @param Post $post
     * @param GetPostsDTO $getPostsDTO
     * @return Paginator<PostComment>
     */
    public function findByPostPaginated(Post $post, GetPostsDTO $getPostsDTO): Paginator
    {
        $page = $this->normalizePage($getPostsDTO->page);

        return $this->postCommentRepository->findByPostPaginated(
            $post,
            self::RESULTS_PER_PAGE,
            $this->getOffset($page, self::RESULTS_PER_PAGE),
        );
    }

    /**
     * @param User $user
     * @param GetPostsDTO $getPostsDTO
     * @return Paginator<PostComment>
     */
    public function findByUserPaginated(User $user, GetPostsDTO $getPostsDTO): Paginator
    {
        $page = $this->normalizePage($getPostsDTO->page);

        return $this->postCommentRepository->findByUserPaginated(
            $user,
            self::RESULTS_PER_PAGE,
            $this->getOffset($page, self::RESULTS_PER_PAGE),
        );
    }

    public function getByUlid(string $ulid): ?PostComment
    {
        return $this->postCommentRepository->findOneByUlid($ulid);
    }

    public function approve(PostComment $postComment): PostComment
    {
        $postComment->setStatus(PostCommentStatus::APPROVED);

        $this->validator->validate($postComment);

        $this->entityManager->persist($postComment);
        $this->entityManager->flush();

        return $postComment;
    }

    public function reject(PostComment $postComment): PostComment
    {
        $postComment->setStatus(PostCommentStatus::REJECTED);

        $this->validator->validate($postComment);

        $this->entityManager->persist($postComment);
        $this->entityManager->flush();

        return $postComment;
    }
}
