<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\SitemapShard;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Sert le contenu déjà généré d'un segment (Phase 17) — jamais recalculé à
 * la lecture, uniquement lu depuis le disque `public` où
 * `GenerateSitemapsAction` l'a écrit.
 *
 * `{type}`/`{index}` lus via `Request::route()`, jamais en paramètres de
 * méthode typés `string` : voir `CityShowController` pour l'explication du
 * contournement — un paramètre résolu par le conteneur (`Country
 * $resolvedCountry`) combiné à des paramètres scalaires typés directement
 * décale leur affectation positionnelle.
 */
class SitemapShardController extends Controller
{
    public function __invoke(Country $resolvedCountry, Request $request): Response
    {
        $type = (string) $request->route('type');
        $index = (string) $request->route('index');

        $pageType = PageType::tryFrom($type);

        if ($pageType === null || ! ctype_digit($index)) {
            throw new ModelNotFoundException;
        }

        $shard = SitemapShard::query()
            ->where('country_id', $resolvedCountry->id)
            ->where('type', $pageType)
            ->where('index', (int) $index)
            ->firstOrFail();

        $content = Storage::disk('public')->get($shard->file_path);

        if ($content === null) {
            throw new ModelNotFoundException;
        }

        return response($content, 200)->header('Content-Type', 'application/xml');
    }
}
