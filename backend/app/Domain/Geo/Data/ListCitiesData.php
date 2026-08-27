<?php

declare(strict_types=1);

namespace App\Domain\Geo\Data;

use Spatie\LaravelData\Data;

class ListCitiesData extends Data
{
    public function __construct(
        public readonly ?string $search = null,
    ) {}
}
