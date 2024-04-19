<?php

namespace App\Api\User\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class UpdateUserDTO extends AbstractDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {
        parent::__construct();
    }

    public static function fromRequest(Request $request): self
    {
        $payload = self::requestContentToArray($request);

        return new self(
            firstName: $payload['firstName'] ?? '',
            lastName: $payload['lastName'] ?? '',
        );
    }
}
