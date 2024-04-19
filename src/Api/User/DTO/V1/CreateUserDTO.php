<?php

declare(strict_types=1);

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class CreateUserDTO extends AbstractDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $plainPassword,
    ) {
        parent::__construct();
    }

    public static function fromRequest(Request $request): self
    {
        $payload = self::requestContentToArray($request);

        return new self(
            firstName: $payload['firstName'] ?? '',
            lastName: $payload['lastName'] ?? '',
            email: $payload['email'] ?? '',
            plainPassword: $payload['password'] ?? '',
        );
    }
}
