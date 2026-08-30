<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Content\Models\ContentSection;
use App\Domain\Content\Models\DistrictContent;
use App\Domain\Content\Queries\DistrictContentBlocksQuery;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Models\District;

it('prefers the district own content over any fallback', function (): void {
    $country = Country::factory()->create();
    $city = City::factory()->for($country)->create();
    $district = District::factory()->for($country)->for($city)->create();

    ContentSection::factory()->create(['key' => 'history', 'scope' => ContentSectionScope::District, 'is_enabled' => true]);
    DistrictContent::factory()->for($district)->create(['section' => 'history', 'status' => ContentStatus::Published, 'body' => 'Histoire du quartier']);

    $blocks = app(DistrictContentBlocksQuery::class)->execute($district);

    expect($blocks)->toHaveCount(1)
        ->and($blocks->first()->body)->toBe('Histoire du quartier');
});

it('chains district -> city -> region in a single call', function (): void {
    $country = Country::factory()->create();
    $region = AdminDivision::factory()->for($country)->create();
    $city = City::factory()->for($country)->create(['admin_division_id' => $region->id]);
    $district = District::factory()->for($country)->for($city)->create();

    ContentSection::factory()->create(['key' => 'nature', 'scope' => ContentSectionScope::District, 'is_enabled' => true]);
    AdminDivisionContent::factory()->for($region, 'adminDivision')->create(['section' => 'nature', 'status' => ContentStatus::Published, 'body' => 'Nature régionale']);

    $blocks = app(DistrictContentBlocksQuery::class)->execute($district);

    expect($blocks)->toHaveCount(1)
        ->and($blocks->first()->body)->toBe('Nature régionale');
});
