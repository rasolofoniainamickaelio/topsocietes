<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Country;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout le pays courant depuis le paramètre de route `{country}` (valeur
 * de `countries.subdomain`) et le lie dans le conteneur pour injection
 * (`Country $country` dans un contrôleur). Aucune API n'est routée par
 * sous-domaine réel : c'est le frontend qui transmet le pays résolu depuis
 * son propre sous-domaine (CLAUDE.md §6.6 — comportement piloté par la
 * table `countries`, jamais par un branchement sur un code pays).
 */
class ResolveCountry
{
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = (string) $request->route('country');

        $country = Country::query()
            ->where('subdomain', $subdomain)
            ->where('is_active', true)
            ->first();

        abort_if($country === null, 404);

        App::instance(Country::class, $country);
        App::setLocale($country->default_locale);

        return $next($request);
    }
}
