<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Actions\ListActivitiesAction;
use App\Http\Api\V1\Resources\ActivityResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityIndexController extends Controller
{
    public function __invoke(Country $resolvedCountry, ListActivitiesAction $action): AnonymousResourceCollection
    {
        return ActivityResource::collection($action->execute($resolvedCountry));
    }
}
