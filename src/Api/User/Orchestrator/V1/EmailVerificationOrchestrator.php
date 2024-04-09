<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\SendEmailVerificationEmailDTO;
use App\Api\User\DTO\V1\VerifyEmailDTO;
use App\Api\User\Exception\InvalidVerificationCodeException;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\V1\EmailVerificationService;
use App\Api\User\Service\V1\UserService;
use App\Api\User\Service\V1\VerificationService;

class EmailVerificationOrchestrator
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly EmailVerificationService $emailService,
        protected readonly VerificationService $verificationService,
        protected readonly ValidatorInterface $validator,
    ) {
        //
    }

    public function verify(VerifyEmailDTO $verifyEmailDTO): void
    {
        $this->validator->validate($verifyEmailDTO);

        $user = $this->userService->findOneBy(['email' => $verifyEmailDTO->email]);

        if ($user === null) {
            throw new InvalidVerificationCodeException();
        }

        $this->verificationService->ensureIsValid(
            VerificationType::EMAIL_VERIFY,
            $user,
            $verifyEmailDTO->code
        );

        $this->emailService->verify($user);
        $this->verificationService->delete(VerificationType::EMAIL_VERIFY, $user);
    }

    public function sendVerificationEmail(SendEmailVerificationEmailDTO $sendEmailVerificationEmailDTO): void
    {
        $this->validator->validate($sendEmailVerificationEmailDTO);

        $user = $this->userService->findOneBy(['email' => $sendEmailVerificationEmailDTO->email]);

        if ($user === null || $user->isEmailVerified()) {
            return;
        }

        $code = $this->verificationService->new(VerificationType::EMAIL_VERIFY, $user);

        $this->emailService->sendVerificationCode($user, $code);
    }
}
