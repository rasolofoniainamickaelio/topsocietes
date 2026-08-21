<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Company\Models\Company;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisputeReport>
 */
class DisputeReportFactory extends Factory
{
    protected $model = DisputeReport::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'field' => 'legal_name',
            'current_value' => fake()->company(),
            'proposed_value' => fake()->company(),
            'reason' => fake()->sentence(),
            'reporter_name' => fake()->name(),
            'reporter_email' => fake()->unique()->safeEmail(),
            'reporter_phone' => fake()->phoneNumber(),
            'evidence_path' => null,
            'status' => DisputeStatus::Submitted,
            'assigned_to' => null,
            'internal_note' => null,
            'resolved_at' => null,
            'ip_hash' => hash('sha256', fake()->ipv4()),
        ];
    }
}
