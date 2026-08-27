<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Models\Redirect;

it('resolves an active redirect', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    Redirect::factory()->for($country)->create([
        'from_path' => '/old-page',
        'to_path' => '/new-page',
        'status_code' => 301,
    ]);

    $response = $this->getJson('/api/v1/fr/resolve?path=/old-page');

    $response->assertOk()
        ->assertJsonPath('data.type', 'redirect')
        ->assertJsonPath('data.to', '/new-page')
        ->assertJsonPath('data.status_code', 301);
});

it('resolves a known route when no redirect matches', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    PageRoute::factory()->for($country)->create(['path' => '/villes/paris']);

    $response = $this->getJson('/api/v1/fr/resolve?path=/villes/paris');

    $response->assertOk()
        ->assertJsonPath('data.type', 'route')
        ->assertJsonPath('data.page_type', 'city');
});

it('returns 404 for an unknown path', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/resolve?path=/does-not-exist');

    $response->assertNotFound();
});

it('rejects a request missing the path parameter', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/resolve');

    $response->assertUnprocessable();
});
