<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Billing\Actions\CreateCheckoutSessionAction;
use App\Domain\Billing\Data\CreateCheckoutSessionData;
use App\Domain\Company\Actions\ShowCompanyAction;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Requests\CreateCheckoutSessionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CompanyCheckoutController extends Controller
{
    public function __invoke(
        Country $resolvedCountry,
        CreateCheckoutSessionRequest $request,
        ShowCompanyAction $showCompany,
        CreateCheckoutSessionAction $createCheckoutSession,
    ): JsonResponse {
        $company = $showCompany->execute($resolvedCountry, (string) $request->route('slug'));

        $this->authorize('update', $company);

        $url = $createCheckoutSession->execute(
            $request->user(),
            $company,
            CreateCheckoutSessionData::from($request->validated()),
        );

        return response()->json(['data' => ['checkout_url' => $url]]);
    }
}
