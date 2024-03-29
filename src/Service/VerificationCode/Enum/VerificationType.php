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
}
