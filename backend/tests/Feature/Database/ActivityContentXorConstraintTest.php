<?php

declare(strict_types=1);

use App\Domain\Content\Models\ActivityContent;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Database\QueryException;

it('accepts a row with only activity_id set', function (): void {
    $content = ActivityContent::factory()->create(['activity_id' => Activity::factory(), 'sector_id' => null]);

    expect($content->exists)->toBeTrue();
});

it('accepts a row with only sector_id set', function (): void {
    $content = ActivityContent::factory()->forSector()->create();

    expect($content->exists)->toBeTrue();
});

it('rejects a row with neither activity_id nor sector_id', function (): void {
    expect(fn () => ActivityContent::factory()->create(['activity_id' => null, 'sector_id' => null]))
        ->toThrow(QueryException::class);
});

it('rejects a row with both activity_id and sector_id', function (): void {
    expect(fn () => ActivityContent::factory()->create([
        'activity_id' => Activity::factory(),
        'sector_id' => Sector::factory(),
    ]))->toThrow(QueryException::class);
});
