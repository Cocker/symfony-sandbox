<?php

namespace App\Tests\Service\VerificationCode;

use App\Service\VerificationCode\StaticVerificationCodeGenerator;
use PHPUnit\Framework\TestCase;

class StaticVerificationCodeGeneratorTest extends TestCase
{
    public function test_it_returns_the_code(): void
    {
        $this->assertSame(StaticVerificationCodeGenerator::CODE, (new StaticVerificationCodeGenerator())->generate());
    }
}
