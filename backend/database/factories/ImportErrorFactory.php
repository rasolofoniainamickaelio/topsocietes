<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\ImportError;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportError>
 */
class ImportErrorFactory extends Factory
{
    protected $model = ImportError::class;

    public function definition(): array
    {
        return [
            'batch_id' => ImportBatch::factory(),
            'row_number' => fake()->numberBetween(1, 10_000),
            'raw_row' => ['siren' => 'invalid'],
            'error_code' => 'invalid_national_id',
            'error_message' => fake()->sentence(),
            'is_resolved' => false,
            'retried_at' => null,
        ];
    }
}
