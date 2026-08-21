<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Ads\Models\AdCampaign;
use App\Domain\Ads\Models\AdSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdCampaign>
 */
class AdCampaignFactory extends Factory
{
    protected $model = AdCampaign::class;

    public function definition(): array
    {
        return [
            'country_id' => null,
            'slot_id' => AdSlot::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'cta_label' => 'En savoir plus',
            'cta_url' => fake()->url(),
            'theme' => '#143A5A',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'weight' => 1,
            'is_active' => true,
            'impressions_count' => 0,
            'clicks_count' => 0,
        ];
    }
}
