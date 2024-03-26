<?php

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class UpdateUserDTO extends AbstractDTO
{
    public string $firstName;
    public string $lastName;

    public static function fromRequest(Request $request): static
    {
        $dto = new static();

        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $dto->firstName = $payload['firstName'] ?? '';
        $dto->lastName = $payload['lastName'] ?? '';

        return $dto;
    }
}
