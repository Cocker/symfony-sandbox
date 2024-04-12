<?php

declare(strict_types=1);

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

readonly class RequestEmailUpdateDTO extends AbstractDTO
{
    #[NotBlank]
    #[Type('string')]
    #[Email]
    public string $newEmail;

    public static function fromRequest(Request $request): static
    {
        $dto = new static();

        $payload = static::requestContentToArray($request);

        $dto->newEmail = $payload['email'] ?? '';

        return $dto;
    }
}
