<?php

namespace App\Service\VerificationCode\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidVerificationCodeException extends HttpException
{
    public function __construct()
    {
        parent::__construct(Response::HTTP_BAD_REQUEST, 'Invalid verification code');
    }
}
