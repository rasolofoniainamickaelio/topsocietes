<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Models\SitemapShard;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Index des sitemaps segmentés d'un pays (Phase 17) — calculé à la lecture
 * depuis `sitemap_shards`, jamais un fichier statique à régénérer en plus
 * des segments eux-mêmes (coût négligeable, une poignée de lignes).
 */
class SitemapIndexController extends Controller
{
    public function __invoke(Country $resolvedCountry): Response
    {
        $baseDomain = (string) config('services.frontend.base_domain');
        $origin = "https://{$resolvedCountry->subdomain}.{$baseDomain}";

        $shards = SitemapShard::query()
            ->where('country_id', $resolvedCountry->id)
            ->orderBy('type')
            ->orderBy('index')
            ->get();

        $entries = $shards->map(fn (SitemapShard $shard) => sprintf(
            '  <sitemap><loc>%s/v1/%s/sitemaps/%s-%d.xml</loc><lastmod>%s</lastmod></sitemap>',
            $origin,
            $resolvedCountry->subdomain,
            $shard->type->value,
            $shard->index,
            $shard->generated_at?->toAtomString(),
        ))->implode("\n");

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            {$entries}
            </sitemapindex>

            XML;

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
