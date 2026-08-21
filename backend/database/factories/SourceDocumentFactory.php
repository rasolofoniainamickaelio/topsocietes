<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\City;
use App\Models\Source;
use App\Models\SourceDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SourceDocument>
 */
class SourceDocumentFactory extends Factory
{
    protected $model = SourceDocument::class;

    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'subject_type' => (new City)->getMorphClass(),
            'subject_id' => City::factory(),
            'url' => fake()->url(),
            'raw_payload' => [],
            'normalized_payload' => [],
            'fetched_at' => now(),
            'hash' => hash('sha256', fake()->uuid()),
            'http_status' => 200,
        ];
    }
}
