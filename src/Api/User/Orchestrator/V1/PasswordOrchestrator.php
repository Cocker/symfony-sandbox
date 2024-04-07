<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use ApiPlatform\Validator\ValidatorInterface;
use App\Api\User\DTO\V1\UpdatePasswordDTO;
use App\Api\User\Service\V1\AuthService;
use App\Api\User\Service\V1\PasswordService;

class PasswordOrchestrator
{
    public function __construct(
        protected readonly ValidatorInterface $validator,
        protected readonly AuthService $authService,
        protected readonly PasswordService $passwordService,
    ) {
        //
    }

    public function update(UpdatePasswordDTO $updatePasswordDTO): void
    {
        $user = $this->authService->getUser();

        if (! $user) {
            return;
        }

        $this->validator->validate($updatePasswordDTO);

        $this->passwordService->update($user, $updatePasswordDTO);
    }
}
