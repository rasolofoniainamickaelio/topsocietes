<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

use App\Domain\Seo\Enums\PageType;
use Spatie\LaravelData\Data;

/**
 * Un lien du maillage interne (Phase 15) : `params` porte tout ce qu'il
 * faut au frontend pour construire l'URL réelle sans que ce Domain ait à
 * connaître la structure d'URL — même esprit que le registre de blocs de
 * contenu (CLAUDE.md §4). Les clés attendues dépendent de `type` : `slug`
 * pour company/city/district/admin_division, `citySlug`+`activitySlug`
 * pour activity_city, `path` (le paramètre, pas la propriété ci-dessous)
 * pour une page éditoriale fixe.
 *
 * `path` (propriété) : le chemin définitif déjà construit (Phase 16, via
 * `BuildCompanyPathAction`), pour les seuls types de page qui ont déjà une
 * route servie par le frontend — `null` sinon (CLAUDE.md §6.5, jamais un
 * lien cassé). Évite de dupliquer la logique de pattern d'URL côté
 * frontend (CLAUDE.md §6.6).
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
        public readonly ?string $path = null,
    ) {}
}
