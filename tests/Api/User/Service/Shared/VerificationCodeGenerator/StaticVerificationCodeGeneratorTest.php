<?php

namespace App\Tests\Api\User\Service\Shared\VerificationCodeGenerator;

use App\Api\User\Service\Shared\VerificationCodeGenerator\StaticVerificationCodeGenerator;
use PHPUnit\Framework\TestCase;

class StaticVerificationCodeGeneratorTest extends TestCase
{
    public function test_it_generates_verification_code(): void
    {
        $codeGenerator = new StaticVerificationCodeGenerator();
        $this->assertSame(StaticVerificationCodeGenerator::CODE, $codeGenerator->generate());
    }
}
