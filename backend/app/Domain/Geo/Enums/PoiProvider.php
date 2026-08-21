<?php

declare(strict_types=1);

namespace App\Domain\Geo\Enums;

enum PoiProvider: string
{
    case OpenStreetMap = 'openstreetmap';
    case Wikidata = 'wikidata';
    case Wikipedia = 'wikipedia';
    case OpenData = 'opendata';
    case Manual = 'manual';
}
