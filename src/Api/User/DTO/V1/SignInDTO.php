<?php

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class SignInDTO extends AbstractDTO
{
    public string $email;
    public string $password;

    public static function fromRequest(Request $request): static
    {
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $dto = new static();

        $dto->email = $payload['email'];
        $dto->password = $payload['password'];

        return $dto;
    }
}
