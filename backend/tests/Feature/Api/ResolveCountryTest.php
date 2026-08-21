<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;

it('resolves an active country from its subdomain', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'code' => 'FR', 'name' => 'France', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr');

    $response->assertOk()
        ->assertJsonPath('data.subdomain', 'fr')
        ->assertJsonPath('data.name', 'France');
});

it('returns 404 for an unknown subdomain', function (): void {
    $response = $this->getJson('/api/v1/zz');

    $response->assertNotFound();
});

it('returns 404 for a disabled country', function (): void {
    Country::factory()->create(['subdomain' => 'be', 'is_active' => false]);

    $response = $this->getJson('/api/v1/be');

    $response->assertNotFound();
});
