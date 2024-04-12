<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\RequestPasswordResetDTO;
use App\Api\User\DTO\V1\ResetPasswordDTO;
use App\Api\User\DTO\V1\UpdatePasswordDTO;
use App\Api\User\Entity\User;
use App\Api\User\Exception\InvalidVerificationCodeException;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\V1\PasswordService;
use App\Api\User\Service\V1\UserService;
use App\Api\User\Service\V1\VerificationService;
use App\Api\User\Voter\UserVoter;
use App\Exception\EntityNotFoundException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PasswordOrchestrator
{
    public function __construct(
        protected readonly ValidatorInterface $validator,
        protected readonly UserService $userService,
        protected readonly PasswordService $passwordService,
        protected readonly VerificationService $verificationService,
        protected readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
        //
    }

    public function update(string $ulid, UpdatePasswordDTO $updatePasswordDTO): void
    {
        $user = $this->userService->getByUlid($ulid);

        if ($user === null || ! $this->authorizationChecker->isGranted(UserVoter::UPDATE, $user)) {
            throw EntityNotFoundException::new(User::class, $ulid);
        }

        $this->validator->validate($updatePasswordDTO);

        $this->passwordService->update($user, $updatePasswordDTO->newPassword);
    }

    public function requestReset(RequestPasswordResetDTO $requestPasswordResetDTO): void
    {
        $this->validator->validate($requestPasswordResetDTO);

        $user = $this->userService->findOneBy(['email' => $requestPasswordResetDTO->email]);

        if ($user === null) {
            return;
        }

        $code = $this->verificationService->new(VerificationType::PASSWORD_RESET, $user);

        $this->passwordService->sendResetPasswordEmail($user, $code);
    }

    public function reset(ResetPasswordDTO $resetPasswordDTO): void
    {
        $this->validator->validate($resetPasswordDTO);

        $user = $this->userService->findOneBy(['email' => $resetPasswordDTO->email]);

        if ($user === null) {
            throw new InvalidVerificationCodeException();
        }

        $this->verificationService->ensureIsValid(
            VerificationType::PASSWORD_RESET,
            $user,
            $resetPasswordDTO->code
        );

        $this->passwordService->update($user, $resetPasswordDTO->password);

        $this->verificationService->delete(VerificationType::PASSWORD_RESET, $user);
    }
}
