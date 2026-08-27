<?php

declare(strict_types=1);

namespace App\Domain\Company\Data;

use Spatie\LaravelData\Data;

class ListCompaniesData extends Data
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $city = null,
        public readonly ?string $activity = null,
    ) {}
}
