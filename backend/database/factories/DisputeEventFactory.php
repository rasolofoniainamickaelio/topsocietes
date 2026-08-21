<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Moderation\Models\DisputeEvent;
use App\Domain\Moderation\Models\DisputeReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisputeEvent>
 */
class DisputeEventFactory extends Factory
{
    protected $model = DisputeEvent::class;

    public function definition(): array
    {
        return [
            'dispute_id' => DisputeReport::factory(),
            'user_id' => null,
            'action' => 'submitted',
            'payload' => [],
        ];
    }
}
