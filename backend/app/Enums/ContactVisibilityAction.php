<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactVisibilityAction: string
{
    case Unmasked = 'unmasked';
    case Masked = 'masked';
    case AdminOverride = 'admin_override';
}
