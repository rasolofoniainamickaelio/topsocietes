<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

/**
 * Portée d'une section référencée dans `content_sections` : à quel type
 * de sujet elle peut s'appliquer. Ensemble fermé (contrairement aux clés
 * de section elles-mêmes, voir `ContentSection`).
 */
enum ContentSectionScope: string
{
    case City = 'city';
    case District = 'district';
    case Activity = 'activity';
    case CityActivity = 'city_activity';
    case Company = 'company';
}
