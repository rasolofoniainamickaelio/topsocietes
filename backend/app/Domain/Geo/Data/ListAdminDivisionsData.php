<?php

declare(strict_types=1);

namespace App\Domain\Geo\Data;

use Spatie\LaravelData\Data;

class ListAdminDivisionsData extends Data
{
    public function __construct(
        public readonly ?int $level = null,
        public readonly ?string $parent = null,
    ) {}
}
