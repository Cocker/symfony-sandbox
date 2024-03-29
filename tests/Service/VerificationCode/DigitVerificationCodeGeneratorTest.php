<?php

namespace App\Tests\Service\VerificationCode;

use App\Service\VerificationCode\DigitVerificationCodeGenerator;
use PHPUnit\Framework\TestCase;

class DigitVerificationCodeGeneratorTest extends TestCase
{
    public function test_it_returns_the_code(): void
    {
        $code = (new DigitVerificationCodeGenerator())->generate();

        $this->assertSame(6, strlen($code));
        $this->assertIsNumeric($code);

        $newCode = (new DigitVerificationCodeGenerator())->generate();

        $this->assertNotSame($code, $newCode);
    }
}
