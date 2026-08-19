<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdDevice;
use App\Models\AdSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdSlot>
 */
class AdSlotFactory extends Factory
{
    protected $model = AdSlot::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'device' => AdDevice::All,
            'position' => 0,
            'is_active' => true,
        ];
    }
}
