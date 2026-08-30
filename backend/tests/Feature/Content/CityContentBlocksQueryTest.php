<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\ContentSection;
use App\Domain\Content\Queries\CityContentBlocksQuery;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;

it('prefers the city own content over the region fallback', function (): void {
    $country = Country::factory()->create();
    $region = AdminDivision::factory()->for($country)->create();
    $city = City::factory()->for($country)->create(['admin_division_id' => $region->id]);

    ContentSection::factory()->create(['key' => 'history', 'scope' => ContentSectionScope::City, 'is_enabled' => true]);

    CityContent::factory()->for($city)->create(['section' => 'history', 'status' => ContentStatus::Published, 'body' => 'Histoire de la ville']);
    AdminDivisionContent::factory()->for($region, 'adminDivision')->create(['section' => 'history', 'status' => ContentStatus::Published, 'body' => 'Histoire de la région']);

    $blocks = app(CityContentBlocksQuery::class)->execute($city);

    expect($blocks)->toHaveCount(1)
        ->and($blocks->first()->body)->toBe('Histoire de la ville');
});

it('falls back to the admin division when the city has nothing for a section', function (): void {
    $country = Country::factory()->create();
    $region = AdminDivision::factory()->for($country)->create();
    $city = City::factory()->for($country)->create(['admin_division_id' => $region->id]);

    ContentSection::factory()->create(['key' => 'nature', 'scope' => ContentSectionScope::City, 'is_enabled' => true]);
    AdminDivisionContent::factory()->for($region, 'adminDivision')->create(['section' => 'nature', 'status' => ContentStatus::Published]);

    $blocks = app(CityContentBlocksQuery::class)->execute($city);

    expect($blocks)->toHaveCount(1)
        ->and($blocks->first()->section)->toBe('nature');
});

it('returns nothing extra when no section is enabled', function (): void {
    $country = Country::factory()->create();
    $city = City::factory()->for($country)->create(['admin_division_id' => null]);

    $blocks = app(CityContentBlocksQuery::class)->execute($city);

    expect($blocks)->toBeEmpty();
});
