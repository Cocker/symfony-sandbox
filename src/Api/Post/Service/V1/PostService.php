<?php

declare(strict_types=1);

namespace App\Api\Post\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\Post\DTO\V1\CreatePostDTO;
use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Entity\Post;
use App\Api\User\Entity\User;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;

class PostService
{
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

    public function complete(Post $post)
    {
        $post->setStatus(PostStatus::PENDING);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function publish(Post $post): Post
    {
        $post->setStatus(PostStatus::PUBLISHED);
        $post->setPublishedAt(CarbonImmutable::now());

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function reject(Post $post): Post
    {
        $post->setStatus(PostStatus::REJECTED);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }
}
