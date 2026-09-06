<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\GenerateSitemapsAction;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Models\SitemapShard;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->country = Country::factory()->create(['subdomain' => 'fr']);
});

it('writes a sitemap shard file containing only indexable routes', function (): void {
    $company = Company::factory()->for($this->country)->create();
    $indexable = PageRoute::factory()->for($this->country)->create([
        'entity_type' => 'company',
        'entity_id' => $company->id,
        'page_type' => PageType::Company,
        'path' => '/entreprise/visible',
        'is_indexable' => true,
    ]);
    PageRoute::factory()->for($this->country)->create([
        'page_type' => PageType::Company,
        'path' => '/entreprise/masquee',
        'is_indexable' => false,
    ]);

    app(GenerateSitemapsAction::class)->execute($this->country);

    $shard = SitemapShard::query()->where('country_id', $this->country->id)->where('type', PageType::Company)->firstOrFail();
    expect($shard->url_count)->toBe(1)
        ->and($shard->is_stale)->toBeFalse();

    $content = Storage::disk('public')->get($shard->file_path);
    expect($content)->toContain('https://fr.topsocietes.com/entreprise/visible')
        ->and($content)->not->toContain('/entreprise/masquee');

    expect($indexable->fresh()->sitemap_shard_id)->toBe($shard->id);
});

it('produces nothing for a page type with no indexable route', function (): void {
    app(GenerateSitemapsAction::class)->execute($this->country);

    expect(SitemapShard::query()->where('country_id', $this->country->id)->count())->toBe(0);
});

it('prunes a shard that no longer has any route once the count shrinks', function (): void {
    PageRoute::factory()->for($this->country)->create(['page_type' => PageType::Company, 'is_indexable' => true]);
    app(GenerateSitemapsAction::class)->execute($this->country);
    $firstRun = SitemapShard::query()->where('country_id', $this->country->id)->where('type', PageType::Company)->firstOrFail();
    Storage::disk('public')->assertExists($firstRun->file_path);

    PageRoute::query()->where('country_id', $this->country->id)->update(['is_indexable' => false]);
    app(GenerateSitemapsAction::class)->execute($this->country);

    expect(SitemapShard::query()->where('id', $firstRun->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($firstRun->file_path);
});
