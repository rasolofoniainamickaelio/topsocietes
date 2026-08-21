<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RedirectReason;
use App\Models\Country;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'from_path' => '/'.fake()->unique()->slug(),
            'to_path' => '/'.fake()->unique()->slug(),
            'status_code' => 301,
            'reason' => RedirectReason::SlugChange,
            'hit_count' => 0,
            'last_hit_at' => null,
            'is_active' => true,
        ];
    }
}
