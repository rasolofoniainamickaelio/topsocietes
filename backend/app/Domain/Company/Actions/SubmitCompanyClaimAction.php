<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Company\Data\SubmitCompanyClaimData;
use App\Domain\Company\Enums\CompanyClaimStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyClaim;
use App\Models\User;

class SubmitCompanyClaimAction
{
    public function execute(User $user, Company $company, SubmitCompanyClaimData $data): CompanyClaim
    {
        return CompanyClaim::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => CompanyClaimStatus::Pending,
            'verification_method' => $data->verification_method,
            'evidence_path' => $data->evidence_path,
        ]);
    }
}
