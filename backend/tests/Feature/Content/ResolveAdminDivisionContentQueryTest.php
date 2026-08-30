<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Content\Queries\ResolveAdminDivisionContentQuery;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\Country;

it('finds a section at the immediate parent level', function (): void {
    $country = Country::factory()->create();
    $region = AdminDivision::factory()->for($country)->create(['level' => 1]);
    $department = AdminDivision::factory()->for($country)->create(['level' => 2, 'parent_id' => $region->id]);

    AdminDivisionContent::factory()->for($department, 'adminDivision')->create([
        'section' => 'nature',
        'status' => ContentStatus::Published,
    ]);

    $result = app(ResolveAdminDivisionContentQuery::class)->execute($department, ['nature']);

    expect($result)->toHaveCount(1)
        ->and($result->first()->section)->toBe('nature');
});

it('climbs to the grandparent when the parent has nothing', function (): void {
    $country = Country::factory()->create();
    $region = AdminDivision::factory()->for($country)->create(['level' => 1]);
    $department = AdminDivision::factory()->for($country)->create(['level' => 2, 'parent_id' => $region->id]);

    AdminDivisionContent::factory()->for($region, 'adminDivision')->create([
        'section' => 'history',
        'status' => ContentStatus::Published,
    ]);

    $result = app(ResolveAdminDivisionContentQuery::class)->execute($department, ['history']);

    expect($result)->toHaveCount(1)
        ->and($result->first()->section)->toBe('history');
});

it('ignores a non-published row', function (): void {
    $country = Country::factory()->create();
    $division = AdminDivision::factory()->for($country)->create();

    AdminDivisionContent::factory()->for($division, 'adminDivision')->create([
        'section' => 'sport',
        'status' => ContentStatus::Draft,
    ]);

    $result = app(ResolveAdminDivisionContentQuery::class)->execute($division, ['sport']);

    expect($result)->toBeEmpty();
});

it('stops searching a section once found and never returns duplicates', function (): void {
    $country = Country::factory()->create();
    $region = AdminDivision::factory()->for($country)->create(['level' => 1]);
    $department = AdminDivision::factory()->for($country)->create(['level' => 2, 'parent_id' => $region->id]);

    AdminDivisionContent::factory()->for($department, 'adminDivision')->create([
        'section' => 'nature',
        'status' => ContentStatus::Published,
    ]);
    AdminDivisionContent::factory()->for($region, 'adminDivision')->create([
        'section' => 'nature',
        'status' => ContentStatus::Published,
    ]);

    $result = app(ResolveAdminDivisionContentQuery::class)->execute($department, ['nature']);

    expect($result)->toHaveCount(1)
        ->and($result->first()->adminDivision->id)->toBe($department->id);
});

it('returns an empty collection when there is nowhere left to climb', function (): void {
    $country = Country::factory()->create();
    $division = AdminDivision::factory()->for($country)->create(['parent_id' => null]);

    $result = app(ResolveAdminDivisionContentQuery::class)->execute($division, ['faq']);

    expect($result)->toBeEmpty();
});
