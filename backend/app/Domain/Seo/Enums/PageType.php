<?php

declare(strict_types=1);

namespace App\Domain\Seo\Enums;

/**
 * Réutilisé sur `routes.page_type`, `sitemap_shards.type` (segmentation
 * des sitemaps par type de page) et `page_publication_rules.page_type`.
 */
enum PageType: string
{
    case Company = 'company';
    case City = 'city';
    case District = 'district';
    case AdminDivision = 'admin_division';
    case Activity = 'activity';
    case ActivityCity = 'activity_city';
    case Sector = 'sector';
    case SectorGeo = 'sector_geo';
    case Country = 'country';
    case Editorial = 'editorial';
}
