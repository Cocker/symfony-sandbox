<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

abstract readonly class AbstractDTO
{
    protected function __construct() {}

    abstract public static function fromRequest(Request $request): static;

    protected static function requestContentToArray(Request $request): array
    {
        return json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
