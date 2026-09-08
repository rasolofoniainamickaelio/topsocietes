<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * `robots.txt` par pays (Phase 17/18) : déclare l'index de sitemaps de CE
 * pays uniquement — jamais un fichier partagé entre pays, chaque
 * sous-domaine devant rester indépendant.
 */
class RobotsController extends Controller
{
    public function __invoke(Country $resolvedCountry): Response
    {
        // Garde-fou préprod (Phase 18, volet applicatif) : bloque
        // inconditionnellement l'indexation hors production, avant même de
        // regarder le pays. Le volet infra (auth HTTP sur le vhost préprod)
        // reste à faire côté serveur (CLAUDE.md §9, hors périmètre ici).
        if (config('app.env') !== 'production') {
            return response("User-agent: *\nDisallow: /\n", 200)->header('Content-Type', 'text/plain');
        }

        $baseDomain = (string) config('services.frontend.base_domain');
        $origin = "https://{$resolvedCountry->subdomain}.{$baseDomain}";

        $body = <<<TXT
            User-agent: *
            Allow: /

            Sitemap: {$origin}/v1/{$resolvedCountry->subdomain}/sitemap.xml

            TXT;

        return response($body, 200)->header('Content-Type', 'text/plain');
    }
}
