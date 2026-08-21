<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Ai\Models\AiGenerationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGenerationLog>
 */
class AiGenerationLogFactory extends Factory
{
    protected $model = AiGenerationLog::class;

    public function definition(): array
    {
        return [
            'generation_job_id' => AiGenerationJob::factory(),
            'input_payload' => [],
            'raw_output' => fake()->paragraph(),
            'validation_report' => [],
        ];
    }
}
