<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Sector;

it('lists all sectors regardless of country, ordered by name', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    Sector::factory()->create(['name' => 'Zoologie']);
    Sector::factory()->create(['name' => 'Artisanat']);

    $response = $this->getJson('/api/v1/fr/sectors');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('name')->all())->toBe(['Artisanat', 'Zoologie']);
});
