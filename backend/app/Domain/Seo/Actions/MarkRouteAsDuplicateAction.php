<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Seo\Enums\NoindexReason;
use App\Domain\Seo\Models\PageRoute;
use InvalidArgumentException;

/**
 * Soft-canonical (Phase 18) : une route doublon reste servie mais pointe
 * vers la route canonique via `canonical_route_id`, et passe en
 * `noindex` avec la raison `duplicate`. Distinct d'une 301
 * (`CreateRedirectAction`) — utile quand deux chemins live doivent
 * coexister sans concurrencer le SEO.
 */
class MarkRouteAsDuplicateAction
{
    public function execute(PageRoute $duplicate, PageRoute $canonical): PageRoute
    {
        if ($duplicate->is($canonical)) {
            throw new InvalidArgumentException('A route cannot be a duplicate of itself.');
        }

        if ($duplicate->country_id !== $canonical->country_id) {
            throw new InvalidArgumentException('Duplicate and canonical routes must belong to the same country.');
        }

        $duplicate->forceFill([
            'canonical_route_id' => $canonical->id,
            'is_indexable' => false,
            'noindex_reason' => NoindexReason::Duplicate,
        ])->save();

        return $duplicate->refresh();
    }
}
