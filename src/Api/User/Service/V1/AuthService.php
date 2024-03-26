<?php

declare(strict_types=1);

namespace App\Api\User\Service\V1;

use App\Api\User\DTO\V1\SignInDTO;
use App\Api\User\Entity\User;
use App\Api\User\Exception\InvalidCredentials;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $JWTTokenManager,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
        //
    }

    public function login(SignInDTO $signInDTO): string
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $signInDTO->email]);
        if (is_null($user) || ! $this->userPasswordHasher->isPasswordValid($user, $signInDTO->password)) {
            throw InvalidCredentials::new();
        }

        return $this->JWTTokenManager->create($user);
    }
}
