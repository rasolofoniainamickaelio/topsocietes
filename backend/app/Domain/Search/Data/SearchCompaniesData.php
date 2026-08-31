<?php

declare(strict_types=1);

namespace App\Domain\Search\Data;

use Spatie\LaravelData\Data;

class SearchCompaniesData extends Data
{
    public function __construct(
        public readonly ?string $term = null,
        public readonly ?string $city = null,
        public readonly ?string $postal_code = null,
        public readonly ?string $activity = null,
        public readonly ?string $sector = null,
        public readonly ?string $admin_division = null,
        public readonly ?string $cursor = null,
        public readonly int $per_page = 20,
    ) {}
}
