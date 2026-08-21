<?php

declare(strict_types=1);

namespace App\Domain\Company\Enums;

enum CompanyStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Ceased = 'ceased';
    case Unknown = 'unknown';
}
