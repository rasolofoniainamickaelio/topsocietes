<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Actions\ResolveCompanyDistrictAction;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\District;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Carré (2.30,48.85)-(2.40,48.90) autour de Paris comme boundary de test :
 * (2.35, 48.87) est dedans, (10.0, 45.0) très loin est dehors.
 */
function squareBoundary(): Expression
{
    return DB::raw("ST_Multi(ST_SetSRID(ST_GeomFromText('POLYGON((2.30 48.85, 2.40 48.85, 2.40 48.90, 2.30 48.90, 2.30 48.85))'), 4326))::geography");
}

it('assigns the district whose boundary contains the company location', function (): void {
    $city = City::factory()->create();
    $district = District::factory()->for($city)->create(['boundary' => squareBoundary()]);
    $company = Company::factory()->for($city)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
    ]);

    app(ResolveCompanyDistrictAction::class)->execute($company);

    expect($company->refresh()->district_id)->toBe($district->id);
});

it('leaves district_id null when no district contains the point', function (): void {
    $city = City::factory()->create();
    District::factory()->for($city)->create(['boundary' => squareBoundary()]);
    $company = Company::factory()->for($city)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(10.0, 45.0), 4326)::geography'),
    ]);

    app(ResolveCompanyDistrictAction::class)->execute($company);

    expect($company->refresh()->district_id)->toBeNull();
});

it('never matches a district belonging to a different city', function (): void {
    $city = City::factory()->create();
    $otherCity = City::factory()->create();
    District::factory()->for($otherCity)->create(['boundary' => squareBoundary()]);
    $company = Company::factory()->for($city)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
    ]);

    app(ResolveCompanyDistrictAction::class)->execute($company);

    expect($company->refresh()->district_id)->toBeNull();
});
