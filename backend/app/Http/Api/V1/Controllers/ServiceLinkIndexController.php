<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Ads\Actions\ListServiceLinksAction;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Resources\ServiceLinkResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceLinkIndexController extends Controller
{
    public function __invoke(Country $resolvedCountry, ListServiceLinksAction $action): AnonymousResourceCollection
    {
        return ServiceLinkResource::collection($action->execute($resolvedCountry));
    }
}
