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

    public function __construct(
        #[\SensitiveParameter] string $password,
        #[\SensitiveParameter] string $newPassword,
    ) {
        $this->password = $password;
        $this->newPassword = $newPassword;

        parent::__construct();
    }

    public static function fromRequest(Request $request): self
    {
        $payload = self::requestContentToArray($request);

        return new self(
            password: $payload['password'] ?? '',
            newPassword: $payload['newPassword'] ?? '',
        );
    }
}
