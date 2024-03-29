<?php

namespace App\Service\VerificationCode;

interface VerificationCodeGeneratorInterface
{
    public function generate(): string;
}
