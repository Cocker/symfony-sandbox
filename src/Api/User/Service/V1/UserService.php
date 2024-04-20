<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\CreateUserDTO;
use App\Api\User\DTO\V1\UpdateUserDTO;
use App\Api\User\Entity\User;
use App\Api\User\Repository\V1\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
        protected readonly UserRepository $userRepository,
    ) {
        //
    }

    public function create(CreateUserDTO $createUserDTO): User
    {
        $user = new User();

        $user->setFirstName($createUserDTO->firstName)
            ->setLastName($createUserDTO->lastName)
            ->setEmail($createUserDTO->email)
            ->setPlainPassword($createUserDTO->plainPassword)
        ;

        $this->validator->validate($user);

        $this->entityManager->persist($user);

        $this->entityManager->flush();

        return $user;
    }

    public function update(User $user, UpdateUserDTO $updateUserDTO): User
    {
        $user->setFirstName($updateUserDTO->firstName)
            ->setLastName($updateUserDTO->lastName)
        ;

        $this->validator->validate($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param array<string, mixed> $criteria
     * @return User|null
     */
    public function findOneBy(array $criteria): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy($criteria);
    }

    public function getByUlid(string $ulid): ?User
    {
        return $this->userRepository->findOneByUlid($ulid);
    }
}
