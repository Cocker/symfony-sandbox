<?php

declare(strict_types=1);

namespace App\Util;

trait PaginationTrait
{
    public function normalizePage(int $page = 1): int
    {
        if ($page < 1) {
            return 1;
        }

        return $page;
    }

    public function getOffset(int $page, int $limit): int
    {
        if ($page !== 0 && $page !== 1) {
            return ($page - 1) * $limit;
        }

        return 0;
    }
}
