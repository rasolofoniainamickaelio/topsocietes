<?php

declare(strict_types=1);

use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Geo\Models\Country;

it('lists active global plans for a country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $global = Plan::factory()->create([
        'code' => 'standard-monthly',
        'country_id' => null,
        'price_cents' => 1900,
        'is_active' => true,
    ]);
    Plan::factory()->create(['code' => 'inactive-plan', 'country_id' => null, 'is_active' => false]);

    $response = $this->getJson('/api/v1/fr/plans');

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', $global->code);
});

it('includes plans scoped to the requested country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);

    Plan::factory()->create(['code' => 'fr-only', 'country_id' => $country->id, 'is_active' => true]);
    Plan::factory()->create(['code' => 'be-only', 'country_id' => $otherCountry->id, 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/plans');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'fr-only');
});

it('orders plans by price ascending', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    Plan::factory()->create(['code' => 'premium', 'country_id' => null, 'price_cents' => 3900, 'is_active' => true]);
    Plan::factory()->create(['code' => 'standard', 'country_id' => null, 'price_cents' => 1900, 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/plans');

    $response->assertOk()
        ->assertJsonPath('data.0.code', 'standard')
        ->assertJsonPath('data.1.code', 'premium');
});

it('exposes the billing period as its raw value', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    Plan::factory()->create(['code' => 'yearly', 'country_id' => null, 'billing_period' => BillingPeriod::Year, 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/plans');

    $response->assertOk()->assertJsonPath('data.0.billing_period', BillingPeriod::Year->value);
});
