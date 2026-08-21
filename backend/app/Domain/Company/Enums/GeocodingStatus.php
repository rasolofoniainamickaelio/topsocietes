<?php

declare(strict_types=1);

namespace App\Domain\Company\Enums;

enum GeocodingStatus: string
{
    case Pending = 'pending';
    case Exact = 'exact';
    case Approximate = 'approximate';
    case CityLevel = 'city_level';
    case Failed = 'failed';
}
