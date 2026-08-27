<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;

it('lists publishable activities scoped to the resolved country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);

    $nomenclature = ActivityNomenclature::factory()->for($country)->create();
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create(['code' => '01.01Z']);
    Activity::factory()->for($nomenclature, 'nomenclature')->create(['is_publishable' => false]);

    $otherNomenclature = ActivityNomenclature::factory()->for($otherCountry)->create();
    Activity::factory()->for($otherNomenclature, 'nomenclature')->create();

    $response = $this->getJson('/api/v1/fr/activities');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('slug')->all())->toBe([$activity->slug]);
});
