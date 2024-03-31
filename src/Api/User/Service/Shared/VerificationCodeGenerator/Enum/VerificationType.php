<?php

declare(strict_types=1);

namespace App\Api\User\Service\Shared\VerificationCodeGenerator\Enum;

use App\Api\User\Entity\User;

enum VerificationType: string
{
    case VERIFY_EMAIL = 'verify_email';

    public function fullKey(User $user): string
    {
        return $this->value . '_' . $user->getId();
    }

    public function ttlSeconds(): int
    {
        return match ($this) {
            self::VERIFY_EMAIL => 24 * 60 * 60,
        };
    }
}
