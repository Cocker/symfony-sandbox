<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use App\Api\User\DTO\V1\SignInDTO;
use App\Api\User\DTO\V1\UpdateUserDTO;
use App\Api\User\Entity\User;
use App\Api\User\Exception\UnauthenticatedException;
use App\Api\User\Service\V1\AuthService;
use App\Api\User\Service\V1\UserService;

class AuthOrchestrator
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly AuthService $authService,
    ) {
        //
    }

    public function login(SignInDTO $signInDTO): string
    {
        $token = $this->authService->login($signInDTO);

        return $token;
    }

    public function getUser(): User
    {
        $user = $this->authService->getUser();

        if ($user === null) {
            throw UnauthenticatedException::new();
        }

        return $user;
    }

    public function updateUser(UpdateUserDTO $updateUserDTO): User
    {
        $user = $this->authService->getUser();

        if ($user === null) {
            throw UnauthenticatedException::new();
        }

        $this->userService->update($user, $updateUserDTO);

        return $user;
    }
}
