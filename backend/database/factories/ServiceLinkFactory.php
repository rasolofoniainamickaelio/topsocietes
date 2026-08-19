<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ServiceLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceLink>
 */
class ServiceLinkFactory extends Factory
{
    protected $model = ServiceLink::class;

    public function definition(): array
    {
        return [
            'country_id' => null,
            'group' => 'formalites',
            'label' => fake()->words(3, true),
            'url' => fake()->url(),
            'display_order' => 0,
            'is_active' => true,
        ];
    }
}
