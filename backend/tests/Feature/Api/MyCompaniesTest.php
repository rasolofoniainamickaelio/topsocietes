<?php

declare(strict_types=1);

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Enums\CompanyClaimStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyClaim;
use App\Domain\Geo\Models\Country;
use App\Models\User;

it('lists only the companies the user holds an approved claim on, with their latest subscription', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $user = User::factory()->create();

    $approved = Company::factory()->for($country)->create();
    CompanyClaim::factory()->for($approved)->for($user)->create(['status' => CompanyClaimStatus::Approved]);
    $subscription = Subscription::factory()->for($approved)->create(['status' => SubscriptionStatus::Active]);

    $pending = Company::factory()->for($country)->create();
    CompanyClaim::factory()->for($pending)->for($user)->create(['status' => CompanyClaimStatus::Pending]);

    $someoneElses = Company::factory()->for($country)->create();
    CompanyClaim::factory()->for($someoneElses)->create(['status' => CompanyClaimStatus::Approved]);

    $response = $this->actingAs($user)->getJson('/api/v1/auth/me/companies');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $approved->slug)
        ->assertJsonPath('data.0.subscription.status', $subscription->status->value);
});

it('rejects an unauthenticated request', function (): void {
    $response = $this->getJson('/api/v1/auth/me/companies');

    $response->assertUnauthorized();
});
