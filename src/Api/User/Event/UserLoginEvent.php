<?php

declare(strict_types=1);

namespace App\Api\User\Event;

use App\Api\User\DTO\V1\SignInDTO;
use Symfony\Contracts\EventDispatcher\Event;

class UserLoginEvent extends Event
{
    public function __construct(
        public readonly int $userId,
        public readonly SignInDTO $signInDTO,
    ) {
        //
    }
}
