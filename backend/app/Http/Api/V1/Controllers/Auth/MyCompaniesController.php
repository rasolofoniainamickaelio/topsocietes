<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers\Auth;

use App\Domain\Company\Actions\ListMyCompaniesAction;
use App\Http\Api\V1\Resources\MyCompanyResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyCompaniesController extends Controller
{
    public function __invoke(Request $request, ListMyCompaniesAction $action): AnonymousResourceCollection
    {
        return MyCompanyResource::collection($action->execute($request->user()));
    }
}
