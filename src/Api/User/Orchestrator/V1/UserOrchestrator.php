<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use App\Api\User\DTO\V1\CreateUserDTO;
use App\Api\User\DTO\V1\UpdateUserDTO;
use App\Api\User\Entity\User;
use App\Api\User\Service\Shared\VerificationCodeGenerator\Enum\VerificationType;
use App\Api\User\Service\V1\EmailVerificationService;
use App\Api\User\Service\V1\UserService;
use App\Api\User\Service\V1\VerificationService;

class UserOrchestrator
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly VerificationService $verificationGeneratorService,
        protected readonly EmailVerificationService $emailVerificationService,
    ) {
        //
    }

    public function create(CreateUserDTO $createUserDTO): User
    {
        $user = $this->userService->create($createUserDTO);

        $code = $this->verificationGeneratorService->new(VerificationType::EMAIL_VERIFY, $user);

        $this->emailVerificationService->sendVerificationCode($user, $code);

        return $user;
    }

    public function update(UpdateUserDTO $updateUserDTO, User $user): User
    {
        return $this->userService->update($user, $updateUserDTO);
    }
}
