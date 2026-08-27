<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\ResolvePathAction;
use App\Http\Api\V1\Requests\ResolvePathRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ResolvePathController extends Controller
{
    public function __invoke(Country $resolvedCountry, ResolvePathRequest $request, ResolvePathAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->execute($resolvedCountry, (string) $request->validated('path')),
        ]);
    }
}
