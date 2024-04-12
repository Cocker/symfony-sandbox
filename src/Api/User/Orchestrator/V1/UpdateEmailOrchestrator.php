<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\RequestEmailUpdateDTO;
use App\Api\User\DTO\V1\VerifyEmailUpdateDTO;
use App\Api\User\Entity\User;
use App\Api\User\Exception\SameEmailException;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\V1\EmailUpdateService;
use App\Api\User\Service\V1\UserService;
use App\Api\User\Service\V1\VerificationService;
use App\Api\User\Voter\UserVoter;
use App\Exception\EntityNotFoundException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UpdateEmailOrchestrator
{
    public function __construct(
        protected readonly ValidatorInterface $validator,
        protected readonly VerificationService $verificationService,
        protected readonly EmailUpdateService $emailUpdateService,
        protected readonly UserService $userService,
        protected readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
        //
    }

    public function requestUpdate(string $ulid, RequestEmailUpdateDTO $requestEmailUpdateDTO): void
    {
        $this->validator->validate($requestEmailUpdateDTO);

        $user = $this->userService->getByUlid($ulid);
        if ($user === null || ! $this->authorizationChecker->isGranted(UserVoter::UPDATE, $user)) {
            throw EntityNotFoundException::new(User::class, $ulid);
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

    public function update(string $userUlid, VerifyEmailUpdateDTO $verifyEmailUpdateDTO): User
    {
        $user = $this->userService->getByUlid($userUlid);

        if ($user === null || ! $this->authorizationChecker->isGranted(UserVoter::UPDATE, $user)) {
            throw EntityNotFoundException::new(User::class, $userUlid);
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
