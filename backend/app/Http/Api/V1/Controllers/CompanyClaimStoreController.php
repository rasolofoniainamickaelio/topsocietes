<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Actions\ShowCompanyAction;
use App\Domain\Company\Actions\SubmitCompanyClaimAction;
use App\Domain\Company\Data\SubmitCompanyClaimData;
use App\Domain\Company\Models\CompanyClaim;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Requests\SubmitCompanyClaimRequest;
use App\Http\Api\V1\Resources\CompanyClaimResource;
use App\Http\Controllers\Controller;

class CompanyClaimStoreController extends Controller
{
    public function __invoke(
        Country $resolvedCountry,
        SubmitCompanyClaimRequest $request,
        ShowCompanyAction $showCompany,
        SubmitCompanyClaimAction $submitClaim,
    ): CompanyClaimResource {
        $this->authorize('create', CompanyClaim::class);

        $company = $showCompany->execute($resolvedCountry, (string) $request->route('slug'));

        return CompanyClaimResource::make(
            $submitClaim->execute($request->user(), $company, SubmitCompanyClaimData::from($request->validated())),
        );
    }
}
