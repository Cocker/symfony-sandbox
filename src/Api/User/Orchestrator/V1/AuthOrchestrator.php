<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use App\Api\User\DTO\V1\SignInDTO;
use App\Api\User\DTO\V1\UpdateUserDTO;
use App\Api\User\Entity\User;
use App\Api\User\Event\UserLoginEvent;
use App\Api\User\Exception\EmailNotVerifiedException;
use App\Api\User\Exception\InvalidCredentialsException;
use App\Api\User\Exception\UnauthenticatedException;
use App\Api\User\Service\V1\AuthService;
use App\Api\User\Service\V1\UserService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class AuthOrchestrator
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly AuthService $authService,
        protected readonly RateLimiterFactory $antiBruteforceLimiter,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {
        //
    }

    public function login(SignInDTO $signInDTO): string
    {
        $limiter = $this->antiBruteforceLimiter->create($signInDTO->email);

        $limiter->consume()->ensureAccepted();

        $user = $this->userService->findOneBy(['email' => $signInDTO->email]);
        if ($user === null) {
            throw InvalidCredentialsException::new();
        }

        if (! $user->isEmailVerified()) {
            throw new EmailNotVerifiedException();
        }

        $token = $this->authService->login($user, $signInDTO);

        $this->eventDispatcher->dispatch(new UserLoginEvent($user->getId(), $signInDTO));

        $limiter->reset();

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
