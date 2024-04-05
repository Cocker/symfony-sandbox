<?php

declare(strict_types=1);

namespace App\Api\User\Service\Shared\VerificationCodeGenerator\Enum;

use App\Api\User\Entity\User;

enum VerificationType: string
{
    case EMAIL_VERIFY = 'email_verify';
    case EMAIL_UPDATE = 'email_update';

    public function fullKey(User $user): string
    {
        $fullKey = $this->value . '_' . $user->getId();

        if ($this === self::EMAIL_UPDATE) {
            // make sure key does not have special characters
            return "{$fullKey}_" . md5($user->getNewEmail());
        }

        return $fullKey;
    }

    public function ttlSeconds(): int
    {
        return match ($this) {
            self::EMAIL_VERIFY => 24 * 60 * 60,
            self::EMAIL_UPDATE => 60 * 60,
        };
    }
}
