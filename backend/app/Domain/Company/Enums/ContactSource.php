<?php

declare(strict_types=1);

namespace App\Domain\Company\Enums;

enum ContactSource: string
{
    case Import = 'import';
    case Owner = 'owner';
    case PublicSource = 'public_source';
    case Admin = 'admin';
}
