<?php

declare(strict_types=1);

namespace App\Domain\Import\Support;

final readonly class ResolvedMoroccoLocation
{
    public function __construct(
        public string $citySlug,
        public ?string $districtSlug,
    ) {}
}
