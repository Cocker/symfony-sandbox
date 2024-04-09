<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\RequestEmailUpdateDTO;
use App\Api\User\DTO\V1\VerifyEmailUpdateDTO;
use App\Api\User\Entity\User;
use App\Api\User\Exception\InvalidVerificationCodeException;
use App\Api\User\Exception\SameEmailException;
use App\Api\User\Exception\UnauthenticatedException;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\V1\AuthService;
use App\Api\User\Service\V1\EmailUpdateService;
use App\Api\User\Service\V1\VerificationService;

class UpdateEmailOrchestrator
{
    public function __construct(
        protected readonly ValidatorInterface $validator,
        protected readonly AuthService $authService,
        protected readonly VerificationService $verificationService,
        protected readonly EmailUpdateService $emailUpdateService,
    ) {
        //
    }

    public function requestUpdate(RequestEmailUpdateDTO $requestEmailUpdateDTO): void
    {
        $this->validator->validate($requestEmailUpdateDTO);

        $user = $this->authService->getUser();

        if ($user === null) {
            return;
        }

        if ($requestEmailUpdateDTO->newEmail === $user->getEmail()) {
            throw new SameEmailException();
        }

        $oldEmail = $user->getEmail();
        $user->setEmail($requestEmailUpdateDTO->newEmail);
        $user->setNewEmail($requestEmailUpdateDTO->newEmail);
        $this->validator->validate($user); // ensure email is not already used

        $user->setEmail($oldEmail);

        $code = $this->verificationService->new(VerificationType::EMAIL_UPDATE, $user);

        $this->emailUpdateService->sendRequestUpdateEmail($user, $code);
    }

    public function update(VerifyEmailUpdateDTO $verifyEmailUpdateDTO): User
    {
        $user = $this->authService->getUser();

        if ($user === null) {
            throw UnauthenticatedException::new();
        }

        $this->validator->validate($verifyEmailUpdateDTO);

        $user->setNewEmail($verifyEmailUpdateDTO->newEmail);

        $this->verificationService->ensureIsValid(
            VerificationType::EMAIL_UPDATE,
            $user,
            $verifyEmailUpdateDTO->code,
        );

        $user = $this->emailUpdateService->verify($user, $verifyEmailUpdateDTO);

        $this->verificationService->delete(VerificationType::EMAIL_UPDATE, $user);

        return $user;
    }
}
