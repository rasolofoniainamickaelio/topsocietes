<?php

declare(strict_types=1);

use App\Domain\Ads\Models\AdCampaign;
use App\Domain\Ads\Models\AdSlot;
use App\Domain\Ads\Models\ServiceLink;
use App\Domain\Geo\Models\Country;

it('lists active campaigns for a slot', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $slot = AdSlot::factory()->create(['code' => 'hero-desktop']);
    $active = AdCampaign::factory()->for($slot, 'slot')->create(['title' => 'Active']);
    AdCampaign::factory()->for($slot, 'slot')->create(['title' => 'Inactive', 'is_active' => false]);
    AdCampaign::factory()->for($slot, 'slot')->create(['title' => 'Expired', 'ends_at' => now()->subDay()]);

    $response = $this->getJson('/api/v1/fr/ad-slots/hero-desktop/campaigns');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('title')->all())->toBe(['Active']);
});

it('excludes campaigns scoped to another country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);
    $slot = AdSlot::factory()->create(['code' => 'sidebar']);
    AdCampaign::factory()->for($slot, 'slot')->create(['country_id' => $otherCountry->id]);
    $global = AdCampaign::factory()->for($slot, 'slot')->create(['country_id' => null, 'title' => 'Global']);

    $response = $this->getJson('/api/v1/fr/ad-slots/sidebar/campaigns');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('title')->all())->toBe(['Global']);
});

it('returns 404 for an unknown ad slot', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/ad-slots/does-not-exist/campaigns');

    $response->assertNotFound();
});

it('lists active service links grouped and ordered', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    ServiceLink::factory()->create(['label' => 'Second', 'display_order' => 2]);
    ServiceLink::factory()->create(['label' => 'First', 'display_order' => 1]);
    ServiceLink::factory()->create(['label' => 'Hidden', 'is_active' => false]);

    $response = $this->getJson('/api/v1/fr/service-links');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('label')->all())->toBe(['First', 'Second']);
});
