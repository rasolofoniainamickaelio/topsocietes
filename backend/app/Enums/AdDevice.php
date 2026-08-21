<?php

declare(strict_types=1);

namespace App\Enums;

enum AdDevice: string
{
    case Desktop = 'desktop';
    case Mobile = 'mobile';
    case All = 'all';
}
