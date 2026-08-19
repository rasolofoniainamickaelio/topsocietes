<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CityContent;
use App\Models\ContentSourceLink;
use App\Models\Fact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentSourceLink>
 */
class ContentSourceLinkFactory extends Factory
{
    protected $model = ContentSourceLink::class;

    public function definition(): array
    {
        return [
            'content_type' => (new CityContent)->getMorphClass(),
            'content_id' => CityContent::factory(),
            'fact_id' => Fact::factory(),
            'source_document_id' => null,
        ];
    }
}
