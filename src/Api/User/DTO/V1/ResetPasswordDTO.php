<?php

declare(strict_types=1);

namespace App\Api\User\DTO\V1;

use App\Api\User\Validator\DigitVerificationCode;
use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

readonly class ResetPasswordDTO extends AbstractDTO
{
    #[NotBlank]
    #[Type('string')]
    #[Email]
    public string $email;

    public string $password;

    #[NotBlank]
    #[DigitVerificationCode]
    public string $code;

    public static function fromRequest(Request $request): static
    {
        $payload = static::requestContentToArray($request);

        $dto = new static();

        $dto->email = $payload['email'] ?? '';
        $dto->password = $payload['password'] ?? '';
        $dto->code = $payload['code'] ?? '';

        return $dto;
    }
}
