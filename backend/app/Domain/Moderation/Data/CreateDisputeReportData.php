<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Data;

use Spatie\LaravelData\Data;

class CreateDisputeReportData extends Data
{
    public function __construct(
        public readonly string $field,
        public readonly ?string $current_value,
        public readonly string $proposed_value,
        public readonly string $reason,
        public readonly string $reporter_name,
        public readonly string $reporter_email,
        public readonly ?string $reporter_phone,
    ) {}
}
