<?php

declare(strict_types=1);

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

readonly class VerifyEmailUpdateDTO extends AbstractDTO
{
    #[NotBlank]
    #[Length(exactly: 6)]
    #[Type('string')]
    public string $code;

    #[NotBlank]
    #[Type('string')]
    #[Email]
    public string $newEmail;

    public static function fromRequest(Request $request): static
    {
        $payload = static::requestContentToArray($request);

        $dto = new static();

        $dto->newEmail = $payload['newEmail'] ?? '';
        $dto->code = $payload['code'] ?? '';

        return $dto;
    }
}
