<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentSectionScope;
use App\Models\AiPrompt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiPrompt>
 */
class AiPromptFactory extends Factory
{
    protected $model = AiPrompt::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'version' => 'v1',
            'scope' => ContentSectionScope::City,
            'system_prompt' => fake()->paragraph(),
            'user_template' => fake()->paragraph(),
            'model' => 'claude-sonnet-5',
            'parameters' => [],
            'is_active' => true,
        ];
    }
}
