<?php

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class SignInDTO extends AbstractDTO
{
    public string $email;
    public string $password;
    public string $ip;
    public string $userAgent;

    public static function fromRequest(Request $request): static
    {
        $payload = static::requestContentToArray($request);

        $dto = new static();

        $dto->email = $payload['email'];
        $dto->password = $payload['password'];
        $dto->ip = $request->getClientIp();
        $dto->userAgent = $request->headers->get('User-Agent', 'Unknown');

        return $dto;
    }
}
