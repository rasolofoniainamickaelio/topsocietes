<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\SitemapShard;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
});

it('lists sitemap shards in the sitemap index', function (): void {
    SitemapShard::factory()->for($this->country)->create([
        'type' => PageType::Company,
        'index' => 0,
        'generated_at' => now(),
    ]);

    $response = $this->get('/api/v1/fr/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('/v1/fr/sitemaps/company-0.xml', false);
});

it('serves the raw content of a sitemap shard file', function (): void {
    Storage::disk('public')->put('sitemaps/fr/company-0.xml', '<urlset><url><loc>test</loc></url></urlset>');
    SitemapShard::factory()->for($this->country)->create([
        'type' => PageType::Company,
        'index' => 0,
        'file_path' => 'sitemaps/fr/company-0.xml',
    ]);

    $response = $this->get('/api/v1/fr/sitemaps/company-0.xml');

    $response->assertOk()->assertSee('<loc>test</loc>', false);
});

it('returns 404 for an unknown shard', function (): void {
    $response = $this->get('/api/v1/fr/sitemaps/company-99.xml');

    $response->assertNotFound();
});

it('declares the sitemap index in robots.txt', function (): void {
    $response = $this->get('/api/v1/fr/robots.txt');

    $response->assertOk()
        ->assertSee('Sitemap: https://fr.topsocietes.com/v1/fr/sitemap.xml', false);
});
