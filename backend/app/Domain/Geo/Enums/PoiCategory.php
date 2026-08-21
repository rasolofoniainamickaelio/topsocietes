<?php

declare(strict_types=1);

namespace App\Domain\Geo\Enums;

enum PoiCategory: string
{
    case ToSee = 'to_see';
    case Leisure = 'leisure';
    case Historical = 'historical';
    case Heritage = 'heritage';
    case Culture = 'culture';
    case Monument = 'monument';
    case Nature = 'nature';
    case Sport = 'sport';
    case Transport = 'transport';
}
