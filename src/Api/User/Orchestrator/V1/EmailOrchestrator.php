<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\SendEmailVerificationEmailDTO;
use App\Api\User\DTO\V1\VerifyEmailDTO;
use App\Api\User\Service\V1\EmailService;
use App\Api\User\Service\V1\UserService;
use App\Service\VerificationCode\Exception\InvalidVerificationCodeException;

class EmailOrchestrator
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly EmailService $emailService,
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

        $this->emailService->verify($user, $verifyEmailDTO->code);
    }

    public function sendVerificationEmail(SendEmailVerificationEmailDTO $sendEmailVerificationEmailDTO): void
    {
        $this->validator->validate($sendEmailVerificationEmailDTO);

        $user = $this->userService->findOneBy(['email' => $sendEmailVerificationEmailDTO->email]);

        if ($user === null) {
            return;
        }

        $this->emailService->sendVerificationCode($user);
    }
}
