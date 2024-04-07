<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\UpdatePasswordDTO;
use App\Api\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PasswordService
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
    ) {
        //
    }

    public function update(User $user, UpdatePasswordDTO $updatePasswordDTO): User
    {
        $user->setPlainPassword($updatePasswordDTO->newPassword);

        $this->validator->validate($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
