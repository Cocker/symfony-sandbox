<?php

declare(strict_types=1);

namespace App\Api\User\Orchestrator\V1;

use App\Api\User\DTO\V1\CreateUserDTO;
use App\Api\User\DTO\V1\UpdateUserDTO;
use App\Api\User\Entity\User;
use App\Api\User\Service\V1\EmailService;
use App\Api\User\Service\V1\UserService;

class UserOrchestrator
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly EmailService $emailService,
    ) {
        //
    }

    public function create(CreateUserDTO $createUserDTO): User
    {
        $user = $this->userService->create($createUserDTO);

        $this->emailService->sendVerificationCode($user);

        return $user;
    }

    public function update(UpdateUserDTO $updateUserDTO, User $user): User
    {
        return $this->userService->update($user, $updateUserDTO);
    }
}
