<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JobRunStatus;
use App\Models\JobRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobRun>
 */
class JobRunFactory extends Factory
{
    protected $model = JobRun::class;

    public function definition(): array
    {
        return [
            'name' => 'App\\Domain\\Import\\Jobs\\ImportCompaniesJob',
            'queue' => 'default',
            'status' => JobRunStatus::Completed,
            'payload' => [],
            'output' => null,
            'exception' => null,
            'attempts' => 1,
            'progress_current' => null,
            'progress_total' => null,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'duration_ms' => 300000,
        ];
    }
}
