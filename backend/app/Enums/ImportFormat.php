<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportFormat: string
{
    case Csv = 'csv';
    case Json = 'json';
    case Xml = 'xml';
}
