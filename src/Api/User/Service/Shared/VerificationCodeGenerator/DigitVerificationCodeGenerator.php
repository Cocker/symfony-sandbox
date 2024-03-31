<?php

declare(strict_types=1);

namespace App\Api\User\Service\Shared\VerificationCodeGenerator;

class DigitVerificationCodeGenerator implements VerificationCodeGeneratorInterface
{
    public function generate(): string
    {
        return (string) random_int(100000, 999999);
    }
}
