<?php

declare(strict_types=1);

namespace App\Domain\Billing\Enums;

enum BillingPeriod: string
{
    case Month = 'month';
    case Year = 'year';
}
