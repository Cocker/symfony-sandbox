<?php

namespace App\Api\User\Service\Shared\VerificationCodeGenerator;

interface VerificationCodeGeneratorInterface
{
    public function generate(): string;
}
