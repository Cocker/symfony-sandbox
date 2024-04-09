<?php

declare(strict_types=1);

namespace App\Api\User\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DigitVerificationCodeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof DigitVerificationCode) {
            throw new UnexpectedTypeException($constraint, DigitVerificationCode::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $isValid = is_string($value) && strlen($value) === 6 && is_numeric($value);

        if ($isValid) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->addViolation();
    }
}
