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
use App\Api\User\Voter\UserVoter;
use App\Exception\EntityNotFoundException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserOrchestrator
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly VerificationService $verificationGeneratorService,
        protected readonly EmailVerificationService $emailVerificationService,
        protected readonly AuthorizationCheckerInterface $authorizationChecker,
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

    public function getByUlid(string $ulid): User
    {
        $user = $this->userService->getByUlid($ulid);

        if (null === $user || ! $this->authorizationChecker->isGranted(UserVoter::VIEW, $user)) {
            throw EntityNotFoundException::new(User::class, $ulid);
        }

        return $user;
    }


    public function update(string $ulid, UpdateUserDTO $updateUserDTO): User
    {
        $user = $this->userService->getByUlid($ulid);

        if ($user === null || ! $this->authorizationChecker->isGranted(UserVoter::UPDATE, $user)) {
            throw EntityNotFoundException::new(User::class, $ulid);
        }

        $this->userService->update($user, $updateUserDTO);

        return $user;
    }
}
