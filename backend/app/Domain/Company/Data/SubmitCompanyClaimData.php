<?php

declare(strict_types=1);

namespace App\Domain\Company\Data;

use App\Domain\Company\Enums\ClaimVerificationMethod;
use Spatie\LaravelData\Data;

class SubmitCompanyClaimData extends Data
{
    public function __construct(
        public readonly ClaimVerificationMethod $verification_method,
        public readonly ?string $evidence_path = null,
    ) {}
}
