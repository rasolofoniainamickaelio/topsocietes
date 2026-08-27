<?php

declare(strict_types=1);

use App\Domain\Billing\Contracts\CheckoutGatewayInterface;
use App\Domain\Billing\Models\Plan;
use App\Domain\Company\Enums\CompanyClaimStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyClaim;
use App\Domain\Geo\Models\Country;
use App\Models\User;

/**
 * Aucun appel réseau réel vers Stripe : le gateway est remplacé par un
 * faux qui retourne une URL fixe — voir docs/adr/0004-billing-auth.md.
 */
beforeEach(function (): void {
    $this->app->bind(CheckoutGatewayInterface::class, fn (): CheckoutGatewayInterface => new class implements CheckoutGatewayInterface
    {
        public function createSubscriptionCheckoutSession(
            User $user,
            Company $company,
            Plan $plan,
            string $successUrl,
            string $cancelUrl,
        ): string {
            return 'https://checkout.stripe.com/fake-session';
        }
    });
});

it('creates a checkout session for a user with an approved claim', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $user = User::factory()->create();
    CompanyClaim::factory()->for($company)->for($user)->create(['status' => CompanyClaimStatus::Approved]);
    $plan = Plan::factory()->create(['code' => 'standard-monthly', 'is_active' => true]);

    $response = $this->actingAs($user)->postJson("/api/v1/fr/companies/{$company->slug}/checkout", [
        'plan_code' => $plan->code,
        'success_url' => 'https://topsocietes.test/success',
        'cancel_url' => 'https://topsocietes.test/cancel',
    ]);

    $response->assertOk()->assertJsonPath('data.checkout_url', 'https://checkout.stripe.com/fake-session');
});

it('rejects checkout for a guest', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $plan = Plan::factory()->create(['code' => 'standard-monthly', 'is_active' => true]);

    $response = $this->postJson("/api/v1/fr/companies/{$company->slug}/checkout", [
        'plan_code' => $plan->code,
        'success_url' => 'https://topsocietes.test/success',
        'cancel_url' => 'https://topsocietes.test/cancel',
    ]);

    $response->assertUnauthorized();
});

it('rejects checkout for an authenticated user without an approved claim', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['code' => 'standard-monthly', 'is_active' => true]);

    $response = $this->actingAs($user)->postJson("/api/v1/fr/companies/{$company->slug}/checkout", [
        'plan_code' => $plan->code,
        'success_url' => 'https://topsocietes.test/success',
        'cancel_url' => 'https://topsocietes.test/cancel',
    ]);

    $response->assertForbidden();
});

it('rejects checkout for a still-pending claim', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $user = User::factory()->create();
    CompanyClaim::factory()->for($company)->for($user)->create(['status' => CompanyClaimStatus::Pending]);
    $plan = Plan::factory()->create(['code' => 'standard-monthly', 'is_active' => true]);

    $response = $this->actingAs($user)->postJson("/api/v1/fr/companies/{$company->slug}/checkout", [
        'plan_code' => $plan->code,
        'success_url' => 'https://topsocietes.test/success',
        'cancel_url' => 'https://topsocietes.test/cancel',
    ]);

    $response->assertForbidden();
});

it('returns 404 for an unknown plan code', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $user = User::factory()->create();
    CompanyClaim::factory()->for($company)->for($user)->create(['status' => CompanyClaimStatus::Approved]);

    $response = $this->actingAs($user)->postJson("/api/v1/fr/companies/{$company->slug}/checkout", [
        'plan_code' => 'does-not-exist',
        'success_url' => 'https://topsocietes.test/success',
        'cancel_url' => 'https://topsocietes.test/cancel',
    ]);

    $response->assertNotFound();
});
