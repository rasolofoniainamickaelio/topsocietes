<?php

declare(strict_types=1);

namespace App\Domain\Ai\Data;

use Spatie\LaravelData\Data;

class ValidationReportData extends Data
{
    /**
     * @param  array<int, string>  $issues
     */
    public function __construct(
        public readonly bool $passed,
        public readonly bool $insufficientData,
        public readonly array $issues,
    ) {}
}
