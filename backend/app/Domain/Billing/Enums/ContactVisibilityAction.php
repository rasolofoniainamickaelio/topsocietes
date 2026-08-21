<?php

declare(strict_types=1);

namespace App\Domain\Billing\Enums;

enum ContactVisibilityAction: string
{
    case Unmasked = 'unmasked';
    case Masked = 'masked';
    case AdminOverride = 'admin_override';
}
