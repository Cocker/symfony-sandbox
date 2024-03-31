<?php

namespace App\Tests\Api\User\Service\Shared\VerificationCodeGenerator;

use App\Api\User\Service\Shared\VerificationCodeGenerator\DigitVerificationCodeGenerator;
use PHPUnit\Framework\TestCase;

class DigitVerificationCodeGeneratorTest extends TestCase
{
    public function test_it_generates_verification_code(): void
    {
        $codeGenerator = new DigitVerificationCodeGenerator();
        $this->assertSame(6, strlen($code = $codeGenerator->generate()));
        $this->assertIsNumeric($code);
    }
}
