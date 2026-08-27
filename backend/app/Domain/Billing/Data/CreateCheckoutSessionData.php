<?php

declare(strict_types=1);

namespace App\Domain\Billing\Data;

use Spatie\LaravelData\Data;

class CreateCheckoutSessionData extends Data
{
    public function __construct(
        public readonly string $plan_code,
        public readonly string $success_url,
        public readonly string $cancel_url,
    ) {}
}
