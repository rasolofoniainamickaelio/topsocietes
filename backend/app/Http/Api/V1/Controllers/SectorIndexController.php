<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Taxonomy\Actions\ListSectorsAction;
use App\Http\Api\V1\Resources\SectorResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SectorIndexController extends Controller
{
    public function __invoke(ListSectorsAction $action): AnonymousResourceCollection
    {
        return SectorResource::collection($action->execute());
    }
}
