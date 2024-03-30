<?php

declare(strict_types=1);

namespace App\Service\VerificationCode\Enum;

enum VerificationType: string
{
    case VERIFY_EMAIL = 'verify_email';

    public function fullKey(string $uniqueId): string
    {
        return "$this->value:$uniqueId";
    }

    public function ttlSeconds(): int
    {
        return match ($this) {
            self::VERIFY_EMAIL => 5 * 60,
        };
    }
}
