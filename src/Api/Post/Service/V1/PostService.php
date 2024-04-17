<?php

declare(strict_types=1);

namespace App\Api\Post\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\Post\DTO\V1\CreatePostDTO;
use App\Api\Post\DTO\V1\GetPostsDTO;
use App\Api\Post\DTO\V1\UpdatePostDTO;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Post;
use App\Api\User\Entity\User;
use App\Util\PaginationTrait;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use LDAP\Result;

class PostService
{
    use PaginationTrait;

    public const int RESULTS_PER_PAGE = 10;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
    ) {
    }

    public function create(User $user, CreatePostDTO $createPostDTO): Post
    {
        $post = new Post();

        $post
            ->setBody($createPostDTO->body)
            ->setTitle($createPostDTO->title)
            ->setAuthor($user)
        ;

        $this->validator->validate($post);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function getByUlid(string $ulid): ?Post
    {
        return $this->entityManager->getRepository(Post::class)->findOneByUlid($ulid);
    }

    public function complete(Post $post): Post
    {
        $post->setStatus(PostStatus::PENDING);

        $this->validator->validate($post);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function publish(Post $post): Post
    {
        $post->setStatus(PostStatus::PUBLISHED);
        $post->setPublishedAt(CarbonImmutable::now());

        $this->validator->validate($post);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function reject(Post $post): Post
    {
        $post->setStatus(PostStatus::REJECTED);

        $this->validator->validate($post);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function update(Post $post, UpdatePostDTO $updatePostDTO): Post
    {
        $post->setTitle($updatePostDTO->title);
        $post->setBody($updatePostDTO->body);

        $this->validator->validate($post);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function getAllPaginated(GetPostsDTO $getPostsDTO): Paginator
    {
        $page = $this->normalizePage($getPostsDTO->page);

        return $this->entityManager->getRepository(Post::class)
            ->findAllPaginated(
                self::RESULTS_PER_PAGE,
                $this->getOffset($page, self::RESULTS_PER_PAGE),
                $getPostsDTO->postStatus,
            )
        ;
    }

    public function getByUserPaginated(User $user, GetPostsDTO $getPostsDTO): Paginator
    {
        $page = $this->normalizePage($getPostsDTO->page);

        return $this->entityManager->getRepository(Post::class)
            ->findByUserPaginated(
                $user,
                self::RESULTS_PER_PAGE,
                $this->getOffset($page, self::RESULTS_PER_PAGE),
                $getPostsDTO->postStatus,
            )
       ;
    }
}
