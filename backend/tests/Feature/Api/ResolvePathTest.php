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

it('follows a manually created redirect chain to its final destination, never returning an intermediate hop', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    Redirect::factory()->for($country)->create(['from_path' => '/a', 'to_path' => '/b']);
    Redirect::factory()->for($country)->create(['from_path' => '/b', 'to_path' => '/c']);

    $response = $this->getJson('/api/v1/fr/resolve?path=/a');

    $response->assertOk()->assertJsonPath('data.to', '/c');
});

it('resolves a known route when no redirect matches', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    PageRoute::factory()->for($country)->create(['path' => '/villes/paris']);

    $response = $this->getJson('/api/v1/fr/resolve?path=/villes/paris');

    $response->assertOk()
        ->assertJsonPath('data.type', 'route')
        ->assertJsonPath('data.page_type', 'city')
        ->assertJsonPath('data.canonical_path', null);
});

it('exposes the soft-canonical path when a route points to another route', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $canonical = PageRoute::factory()->for($country)->create(['path' => '/villes/paris']);
    PageRoute::factory()->for($country)->create([
        'path' => '/paris',
        'canonical_route_id' => $canonical->id,
        'is_indexable' => false,
    ]);

    $response = $this->getJson('/api/v1/fr/resolve?path=/paris');

    $response->assertOk()
        ->assertJsonPath('data.type', 'route')
        ->assertJsonPath('data.is_indexable', false)
        ->assertJsonPath('data.canonical_path', '/villes/paris');
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
