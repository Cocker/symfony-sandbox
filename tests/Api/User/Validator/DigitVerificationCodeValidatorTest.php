<?php

declare(strict_types=1);

namespace App\Tests\Api\User\Validator;

use App\Api\User\Validator\DigitVerificationCode;
use App\Api\User\Validator\DigitVerificationCodeValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DigitVerificationCodeValidatorTest extends ConstraintValidatorTestCase
{
    private const string FAILED_VALIDATION_MESSAGE = 'The verification code should be a numeric value of length 6. You provided: {{ value }}';

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new DigitVerificationCodeValidator();
    }

    public function test_it_does_not_handle_null_values(): void
    {
        $this->validator->validate(null, new DigitVerificationCode());

        $this->assertNoViolation();
    }

    public function test_it_does_not_handle_empty_strings(): void
    {
        $this->validator->validate('', new DigitVerificationCode());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function test_smaller_length_is_invalid(DigitVerificationCode $constraint): void
    {
        $this->validator->validate($value = '12345', $constraint);

        $this->buildViolation(self::FAILED_VALIDATION_MESSAGE)
            ->setParameter('{{ value }}', '"' . $value . '"')
            ->assertRaised();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function test_bigger_length_is_invalid(DigitVerificationCode $constraint): void
    {
        $this->validator->validate($value = '1234567', $constraint);

        $this->buildViolation(self::FAILED_VALIDATION_MESSAGE)
            ->setParameter('{{ value }}', '"' . $value . '"')
            ->assertRaised();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function test_non_numeric_is_invalid(DigitVerificationCode $constraint): void
    {
        $this->validator->validate($value = 'abc123', $constraint);

        $this->buildViolation(self::FAILED_VALIDATION_MESSAGE)
            ->setParameter('{{ value }}', '"' . $value . '"')
            ->assertRaised();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function test_6_digits_is_valid(DigitVerificationCode $constraint): void
    {
        $this->validator->validate('123456', $constraint);

        $this->assertNoViolation();
    }

    public function provideInvalidConstraints(): \Generator
    {
        yield [new DigitVerificationCode(message: self::FAILED_VALIDATION_MESSAGE)];
    }
}
