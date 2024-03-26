<?php

namespace App\Api\User\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthenticatedException extends HttpException
{
    public static function new(): static
    {
        return new static(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
    }
}
