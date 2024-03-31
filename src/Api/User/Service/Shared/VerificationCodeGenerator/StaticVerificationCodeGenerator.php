<?php

declare(strict_types=1);

namespace App\Api\User\Service\Shared\VerificationCodeGenerator;

class StaticVerificationCodeGenerator implements VerificationCodeGeneratorInterface
{
    public final const string CODE = '111111';

    public function generate(): string
    {
        return self::CODE;
    }
}