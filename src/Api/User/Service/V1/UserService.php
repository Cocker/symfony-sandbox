<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\CreateUserDTO;
use App\Api\User\DTO\V1\UpdateUserDTO;
use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
        protected readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
        //
    }

    public function create(CreateUserDTO $createUserDTO): User
    {
        $user = new User();

        $user->setFirstName($createUserDTO->firstName)
            ->setLastName($createUserDTO->lastName)
            ->setEmail($createUserDTO->email)
            ->setStatus(UserStatus::ACTIVE)
            ->setPassword($createUserDTO->password)
        ;

        $this->validator->validate($user);

        // after password was validated we can hash it
        $hashedPassword = $this->userPasswordHasher->hashPassword($user, $createUserDTO->password);
        $user->setPassword($hashedPassword);

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
}
