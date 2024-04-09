<?php

declare(strict_types=1);

namespace App\Api\User\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DigitVerificationCode extends Constraint
{
    public string $message = 'The verification code should be a numeric value of length 6. You provided: {{ value }}';

    public function __construct(string $message = null, array $groups = null, $payload = null)
    {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
