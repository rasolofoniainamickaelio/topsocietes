<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Company\Models\Company;

/**
 * Construit le chemin public d'une fiche entreprise à partir du gabarit
 * configuré par pays (`countries.url_patterns.company`, CLAUDE.md §6.6 —
 * jamais une structure codée en dur). Utilisée à la fois pour résoudre le
 * chemin courant et, sur renommage, l'ancien chemin (Phase 16).
 */
class BuildCompanyPathAction
{
    private const DEFAULT_PATTERN = '/{city}/{slug}-{public_id}';

    public function execute(Company $company, ?string $slugOverride = null, ?string $citySlugOverride = null): string
    {
        $urlPatterns = $company->country->url_patterns ?? [];
        $pattern = is_string($urlPatterns['company'] ?? null) ? $urlPatterns['company'] : self::DEFAULT_PATTERN;

        return strtr($pattern, [
            '{city}' => $citySlugOverride ?? $this->citySlug($company),
            '{slug}' => $slugOverride ?? $company->slug,
            '{public_id}' => $company->public_id,
        ]);
    }

    private function citySlug(Company $company): string
    {
        return $company->city !== null ? $company->city->slug : 'entreprise';
    }
}
