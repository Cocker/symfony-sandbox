<?php

declare(strict_types=1);

namespace App\Api\User\DTO\V1;

use App\Api\User\Validator\DigitVerificationCode;
use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

readonly class ResetPasswordDTO extends AbstractDTO
{
    #[NotBlank]
    #[Type('string')]
    #[Email]
    public string $email;

    public string $password;

    #[NotBlank]
    #[DigitVerificationCode]
    public string $code;

    public function __construct(
        string $email,
        #[\SensitiveParameter] string $password,
        #[\SensitiveParameter] string $code,
    ) {
       $this->email = $email;
       $this->password = $password;
       $this->code = $code;

       parent::__construct();
    }

    public static function fromRequest(Request $request): self
    {
        $payload = self::requestContentToArray($request);

        return new self(
            email: $payload['email'] ?? '',
            password: $payload['password'] ?? '',
            code: $payload['code'] ?? '',
        );
    }
}
