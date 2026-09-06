<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

use App\Domain\Seo\Enums\PageType;
use Spatie\LaravelData\Data;

/**
 * Un lien du maillage interne (Phase 15) : `params` porte tout ce qu'il
 * faut au frontend pour construire l'URL réelle sans que ce Domain ait à
 * connaître la structure d'URL (Phase 16, pas encore figée) — même esprit
 * que le registre de blocs de contenu (CLAUDE.md §4). Les clés attendues
 * dépendent de `type` : `slug` pour company/city/district/admin_division,
 * `citySlug`+`activitySlug` pour activity_city, `path` pour une page
 * éditoriale fixe.
 */
class InternalLinkData extends Data
{
    /**
     * @param  array<string, string>  $params
     */
    public function __construct(
        public readonly PageType $type,
        public readonly string $label,
        public readonly array $params,
    ) {}
}
