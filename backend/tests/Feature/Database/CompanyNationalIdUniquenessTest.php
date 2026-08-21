<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\QueryException;

it('rejects a duplicate national_id within the same country', function (): void {
    $country = Country::factory()->create();

    Company::factory()->create(['country_id' => $country->id, 'national_id' => '123456789']);

    expect(fn () => Company::factory()->create(['country_id' => $country->id, 'national_id' => '123456789']))
        ->toThrow(QueryException::class);
});

it('allows the same national_id in two different countries', function (): void {
    $countryA = Country::factory()->create();
    $countryB = Country::factory()->create();

    Company::factory()->create(['country_id' => $countryA->id, 'national_id' => '123456789']);
    $second = Company::factory()->create(['country_id' => $countryB->id, 'national_id' => '123456789']);

    expect($second->exists)->toBeTrue();
});
