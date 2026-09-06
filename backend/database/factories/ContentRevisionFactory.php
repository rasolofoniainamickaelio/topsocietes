<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ContentRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentRevision>
 */
class ContentRevisionFactory extends Factory
{
    protected $model = ContentRevision::class;

    public function definition(): array
    {
        return [
            'content_type' => 'city_content',
            'content_id' => 1,
            'locale' => 'fr',
            'section' => 'history',
            'title' => null,
            'body' => fake()->paragraph(),
            'data' => null,
            'status' => ContentStatus::Published,
            'generation_id' => null,
        ];
    }
}
