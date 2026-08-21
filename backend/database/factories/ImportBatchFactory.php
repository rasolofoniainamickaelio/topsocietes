<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ImportFormat;
use App\Enums\ImportStatus;
use App\Models\Country;
use App\Models\ImportBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'filename' => fake()->slug().'.csv',
            'format' => ImportFormat::Csv,
            'mapping_id' => null,
            'total_rows' => 0,
            'processed_rows' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'status' => ImportStatus::Pending,
            'checkpoint' => null,
            'started_at' => null,
            'finished_at' => null,
            'options' => [],
        ];
    }
}
