<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Moderation\Actions\CreateDisputeReportAction;
use App\Domain\Moderation\Data\CreateDisputeReportData;
use App\Http\Api\V1\Requests\CreateDisputeReportRequest;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class DisputeReportStoreController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(Country $resolvedCountry, CreateDisputeReportRequest $request, CreateDisputeReportAction $action): JsonResponse
    {
        $company = Company::query()
            ->where('country_id', $resolvedCountry->id)
            ->where('slug', (string) $request->route('slug'))
            ->first();

        if ($company === null) {
            throw new ModelNotFoundException;
        }

        $validated = $request->validated();
        unset($validated['evidence']);

        $evidencePath = $request->hasFile('evidence')
            ? $request->file('evidence')->store('disputes/evidence', 'local')
            : null;

        $dispute = $action->execute(
            $company,
            CreateDisputeReportData::from([...$validated, 'evidence_path' => $evidencePath]),
            hash('sha256', (string) $request->ip()),
        );

        return response()->json(['data' => ['status' => 'submitted', 'tracking_number' => $dispute->id]], 201);
    }
}
