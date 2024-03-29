<?php

declare(strict_types=1);

namespace App\Service\VerificationCode;

class DigitVerificationCodeGenerator implements VerificationCodeGeneratorInterface
{
    public function generate(): string
    {
        return (string) random_int(100000, 999999);
    }
}
