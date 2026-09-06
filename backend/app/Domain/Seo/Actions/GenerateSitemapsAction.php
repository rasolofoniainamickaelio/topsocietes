<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Models\SitemapShard;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Découpe les `PageRoute` indexables d'un pays en sitemaps segmentés par
 * type de page (Phase 17), jamais plus de 50 000 URL par fichier. Ne
 * sélectionne jamais une 301 ni une page `is_indexable=false` : c'est
 * `PageRoute.is_indexable`, tenu à jour par `EvaluatePagePublicationAction`
 * (Phase 18), qui fait foi.
 *
 * Reconstruit l'ensemble des segments d'un type à chaque exécution plutôt
 * qu'une diffusion incrémentale fine : au volume actuel (un seul type de
 * page réellement routé, Phase 16), le coût d'une reconstruction complète
 * est négligeable — une vraie diffusion incrémentale segment par segment
 * sera introduite si un volume réel l'exige.
 */
class GenerateSitemapsAction
{
    private const MAX_URLS_PER_SHARD = 50_000;

    public function execute(Country $country): void
    {
        $disk = Storage::disk('public');
        $baseDir = "sitemaps/{$country->subdomain}";

        foreach (PageType::cases() as $type) {
            $routes = PageRoute::query()
                ->where('country_id', $country->id)
                ->where('page_type', $type)
                ->where('is_indexable', true)
                ->orderBy('id')
                ->get();

            $chunks = $routes->chunk(self::MAX_URLS_PER_SHARD)->values();

            foreach ($chunks as $index => $chunk) {
                $filePath = "{$baseDir}/{$type->value}-{$index}.xml";
                $disk->put($filePath, $this->buildXml($country, $chunk));

                $shard = SitemapShard::updateOrCreate(
                    ['country_id' => $country->id, 'type' => $type, 'index' => $index],
                    ['url_count' => $chunk->count(), 'file_path' => $filePath, 'generated_at' => now(), 'is_stale' => false],
                );

                PageRoute::query()->whereIn('id', $chunk->pluck('id'))->update(['sitemap_shard_id' => $shard->id]);
            }

            $this->pruneObsoleteShards($country, $type, $chunks->count(), $disk);
        }
    }

    /**
     * @param  Collection<int, PageRoute>  $routes
     */
    private function buildXml(Country $country, Collection $routes): string
    {
        $baseDomain = (string) config('services.frontend.base_domain');
        $origin = "https://{$country->subdomain}.{$baseDomain}";

        $urls = $routes->map(function (PageRoute $route) use ($origin): string {
            $lastmod = ($route->last_modified_at ?? $route->updated_at)->toAtomString();
            $changefreq = $route->changefreq !== null ? "<changefreq>{$route->changefreq}</changefreq>" : '';

            return "  <url><loc>{$origin}{$route->path}</loc>{$changefreq}<lastmod>{$lastmod}</lastmod></url>";
        })->implode("\n");

        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            {$urls}
            </urlset>

            XML;
    }

    /**
     * Supprime les segments devenus surnuméraires (le nombre de routes
     * indexables a diminué depuis la dernière génération).
     */
    private function pruneObsoleteShards(Country $country, PageType $type, int $currentShardCount, Filesystem $disk): void
    {
        SitemapShard::query()
            ->where('country_id', $country->id)
            ->where('type', $type)
            ->where('index', '>=', $currentShardCount)
            ->get()
            ->each(function (SitemapShard $shard) use ($disk): void {
                $disk->delete($shard->file_path);
                $shard->delete();
            });
    }
}
