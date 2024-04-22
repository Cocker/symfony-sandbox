<?php

declare(strict_types=1);

namespace App\Api\Post\DTO\V1;

use App\DTO\AbstractDTO;
use Symfony\Component\HttpFoundation\Request;

readonly class GetPostCommentsDTO extends AbstractDTO
{
    public function __construct(public int $page)
    {
        parent::__construct();
    }

    public static function fromRequest(Request $request): GetPostCommentsDTO
    {
        return new self((int) $request->get('page', 1));
    }
}
