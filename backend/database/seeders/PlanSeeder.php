<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Billing\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Plans génériques (country_id = null : applicables à tous les pays tant
 * qu'aucune grille tarifaire locale n'est définie). `features` reste
 * ouvert (jsonb) plutôt que des colonnes dédiées, pour ajouter des
 * avantages sans migration.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'standard-monthly',
                'name' => 'Standard mensuel',
                'country_id' => null,
                'price_cents' => 1900,
                'currency' => 'EUR',
                'billing_period' => BillingPeriod::Month->value,
                'features' => ['contact_visibility' => true, 'claim_badge' => true],
                'is_active' => true,
            ],
            [
                'code' => 'standard-yearly',
                'name' => 'Standard annuel',
                'country_id' => null,
                'price_cents' => 19000,
                'currency' => 'EUR',
                'billing_period' => BillingPeriod::Year->value,
                'features' => ['contact_visibility' => true, 'claim_badge' => true],
                'is_active' => true,
            ],
            [
                'code' => 'premium-monthly',
                'name' => 'Premium mensuel',
                'country_id' => null,
                'price_cents' => 3900,
                'currency' => 'EUR',
                'billing_period' => BillingPeriod::Month->value,
                'features' => ['contact_visibility' => true, 'claim_badge' => true, 'priority_support' => true, 'stats_dashboard' => true],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['code' => $plan['code']], $plan);
        }
    }
}
