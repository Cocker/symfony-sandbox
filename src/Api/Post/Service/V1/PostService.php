<?php

declare(strict_types=1);

namespace App\Api\Post\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\Post\DTO\V1\CreatePostDTO;
use App\Api\Post\Entity\Post;
use App\Api\User\Entity\User;
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
}
