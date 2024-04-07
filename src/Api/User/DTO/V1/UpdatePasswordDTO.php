<?php

declare(strict_types=1);

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

readonly class UpdatePasswordDTO extends AbstractDTO
{
    #[NotBlank]
    #[UserPassword]
    public string $password;
    public string $newPassword;

    public static function fromRequest(Request $request): static
    {
        $payload = static::requestContentToArray($request);

        $dto = new static;

        $dto->password = $payload['password'] ?? '';
        $dto->newPassword = $payload['newPassword'] ?? '';

        return $dto;
    }
}
