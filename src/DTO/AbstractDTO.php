<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

abstract readonly class AbstractDTO
{
    protected function __construct() {}

    abstract public static function fromRequest(Request $request): static;
}
