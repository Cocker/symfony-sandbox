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

    public function __construct(
        string $email,
        #[\SensitiveParameter] string $password,
        string $ip,
        string $userAgent,
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->ip = $ip;
        $this->userAgent = $userAgent;

        parent::__construct();
    }

    public static function fromRequest(Request $request): self
    {
        $payload = self::requestContentToArray($request);

        return new self(
            email: $payload['email'],
            password: $payload['password'],
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent', 'Unknown'),
        );
    }
}
