<?php

declare(strict_types=1);

namespace App\Domain\Company\Enums;

enum ContactType: string
{
    case Phone = 'phone';
    case Mobile = 'mobile';
    case Email = 'email';
    case Website = 'website';
    case Fax = 'fax';
    case Social = 'social';
}
