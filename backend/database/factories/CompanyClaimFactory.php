<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Company\Enums\ClaimVerificationMethod;
use App\Domain\Company\Enums\CompanyClaimStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyClaim>
 */
class CompanyClaimFactory extends Factory
{
    protected $model = CompanyClaim::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'status' => CompanyClaimStatus::Pending,
            'verification_method' => ClaimVerificationMethod::EmailDomain,
            'evidence_path' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'notes' => null,
        ];
    }
}
