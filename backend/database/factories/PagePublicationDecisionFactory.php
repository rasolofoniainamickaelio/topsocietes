<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PublicationDecision;
use App\Models\PagePublicationDecision;
use App\Models\PageRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PagePublicationDecision>
 */
class PagePublicationDecisionFactory extends Factory
{
    protected $model = PagePublicationDecision::class;

    public function definition(): array
    {
        return [
            'route_id' => PageRoute::factory(),
            'score' => fake()->numberBetween(0, 100),
            'decision' => PublicationDecision::Publish,
            'reasons' => [],
            'evaluated_at' => now(),
        ];
    }
}
