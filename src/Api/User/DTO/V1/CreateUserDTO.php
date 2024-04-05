<?php

declare(strict_types=1);

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class CreateUserDTO extends AbstractDTO
{
    public string $firstName;
    public string $lastName;
    public string $email;
    public string $plainPassword;

    public static function fromRequest(Request $request): static
    {
        $dto = new static();

        $payload = static::requestContentToArray($request);

        $dto->firstName = $payload['firstName'] ?? '';
        $dto->lastName = $payload['lastName'] ?? '';
        $dto->email = $payload['email'] ?? '';
        $dto->plainPassword = $payload['password'] ?? '';

        return $dto;
    }
}
