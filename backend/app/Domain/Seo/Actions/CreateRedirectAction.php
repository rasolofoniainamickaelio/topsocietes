<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\RedirectReason;
use App\Domain\Seo\Models\Redirect;

/**
 * Seul point d'écriture d'une redirection (Phase 16) — jamais de création
 * directe ailleurs, pour garantir la règle "pas de chaîne de redirections" :
 * la nouvelle redirection cible toujours la destination FINALE (jamais un
 * chemin qui redirige lui-même), et toute redirection existante qui visait
 * l'ancien chemin est repointée vers cette même destination finale.
 */
class CreateRedirectAction
{
    private const MAX_CHAIN_HOPS = 5;

    public function execute(Country $country, string $fromPath, string $toPath, RedirectReason $reason): ?Redirect
    {
        if ($fromPath === $toPath) {
            return null;
        }

        $finalTarget = $this->resolveFinalTarget($country, $toPath);

        Redirect::query()
            ->where('country_id', $country->id)
            ->where('to_path', $fromPath)
            ->where('is_active', true)
            ->update(['to_path' => $finalTarget]);

        return Redirect::updateOrCreate(
            ['country_id' => $country->id, 'from_path' => $fromPath],
            ['to_path' => $finalTarget, 'status_code' => 301, 'reason' => $reason, 'is_active' => true],
        );
    }

    /**
     * `$hops` protège contre toute boucle pathologique (ex. créée à la main
     * en back-office) — jamais atteint par une chaîne issue de cette Action
     * elle-même, qui ne produit jamais plus d'un saut.
     */
    private function resolveFinalTarget(Country $country, string $path, int $hops = 0): string
    {
        if ($hops >= self::MAX_CHAIN_HOPS) {
            return $path;
        }

        $next = Redirect::query()
            ->where('country_id', $country->id)
            ->where('from_path', $path)
            ->where('is_active', true)
            ->value('to_path');

        return $next === null ? $path : $this->resolveFinalTarget($country, $next, $hops + 1);
    }
}
