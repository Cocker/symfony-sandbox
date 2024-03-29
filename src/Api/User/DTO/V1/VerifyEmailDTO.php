<?php

declare(strict_types=1);

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

readonly class VerifyEmailDTO extends AbstractDTO
{
    #[NotBlank]
    #[Type('string')]
    #[Email]
    public string $email;

    #[NotBlank]
    #[Length(exactly: 6)]
    #[Type('string')]
    public string $code;

    public static function fromRequest(Request $request): static
    {
        $dto = new static();

        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $dto->email = $payload['email'] ?? '';
        $dto->code = $payload['code'] ?? '';

        return $dto;
    }
}
