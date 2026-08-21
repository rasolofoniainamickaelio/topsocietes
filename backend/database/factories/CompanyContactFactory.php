<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Company\Enums\ContactSource;
use App\Domain\Company\Enums\ContactType;
use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyContact>
 */
class CompanyContactFactory extends Factory
{
    protected $model = CompanyContact::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => ContactType::Phone,
            'value' => fake()->phoneNumber(),
            'is_monetized' => true,
            'visibility' => ContactVisibility::Hidden,
            'source' => ContactSource::Import,
            'verified_at' => null,
        ];
    }
}
