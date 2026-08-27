<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Billing\Actions\ListActivePlansAction;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Resources\PlanResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanIndexController extends Controller
{
    public function __invoke(Country $resolvedCountry, ListActivePlansAction $action): AnonymousResourceCollection
    {
        return PlanResource::collection($action->execute($resolvedCountry));
    }
}
