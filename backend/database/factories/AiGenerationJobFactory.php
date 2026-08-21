<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AiProvider;
use App\Enums\ContentSection;
use App\Enums\GenerationStatus;
use App\Models\AiGenerationJob;
use App\Models\AiPrompt;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGenerationJob>
 */
class AiGenerationJobFactory extends Factory
{
    protected $model = AiGenerationJob::class;

    public function definition(): array
    {
        return [
            'target_type' => (new City)->getMorphClass(),
            'target_id' => City::factory(),
            'section' => ContentSection::History->value,
            'locale' => 'fr',
            'prompt_id' => AiPrompt::factory(),
            'model' => 'claude-sonnet-5',
            'provider' => AiProvider::Anthropic,
            'status' => GenerationStatus::Pending,
            'attempts' => 0,
            'batch_reference' => null,
            'scheduled_at' => now(),
            'started_at' => null,
            'finished_at' => null,
            'duration_ms' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'cost_cents' => null,
            'error_code' => null,
            'error_message' => null,
        ];
    }
}
