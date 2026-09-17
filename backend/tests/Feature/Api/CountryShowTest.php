<?php

declare(strict_types=1);

use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;

it('exposes the country with its path, is_indexable and territory links', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true, 'name' => 'France']);
    $region = AdminDivision::factory()->for($country)->create(['level' => 1, 'name' => 'Bretagne']);
    AdminDivision::factory()->for($country)->create(['level' => 2, 'parent_id' => $region->id]); // pas une région, ne doit pas apparaître
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    Activity::factory()->for($nomenclature, 'nomenclature')->create(['public_label' => 'Plombier', 'is_publishable' => true]);
    Activity::factory()->for($nomenclature, 'nomenclature')->create(['is_publishable' => false]); // ne doit pas apparaître

    $response = $this->getJson('/api/v1/fr/');

    $response->assertOk()
        ->assertJsonPath('data.name', 'France')
        ->assertJsonPath('data.path', '/')
        ->assertJsonPath('data.is_indexable', true)
        ->assertJsonCount(1, 'data.links.regions')
        ->assertJsonPath('data.links.regions.0.label', 'Bretagne')
        ->assertJsonCount(1, 'data.links.activities')
        ->assertJsonPath('data.links.activities.0.label', 'Plombier');
});
