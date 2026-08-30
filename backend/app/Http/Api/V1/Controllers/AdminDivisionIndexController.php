<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Actions\ListAdminDivisionsAction;
use App\Domain\Geo\Data\ListAdminDivisionsData;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Requests\ListAdminDivisionsRequest;
use App\Http\Api\V1\Resources\AdminDivisionResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminDivisionIndexController extends Controller
{
    public function __invoke(Country $resolvedCountry, ListAdminDivisionsRequest $request, ListAdminDivisionsAction $action): AnonymousResourceCollection
    {
        $divisions = $action->execute($resolvedCountry, ListAdminDivisionsData::from($request->validated()));

        return AdminDivisionResource::collection($divisions);
    }
}
