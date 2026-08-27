<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Ads\Actions\ListActiveCampaignsAction;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Resources\AdCampaignResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdSlotCampaignsController extends Controller
{
    // Voir CityShowController : `$slug`/route scalaire combiné à un
    // paramètre injecté dont le nom ne correspond à aucun segment de route
    // désaligne la résolution positionnelle de Laravel.
    public function __invoke(Country $resolvedCountry, Request $request, ListActiveCampaignsAction $action): AnonymousResourceCollection
    {
        return AdCampaignResource::collection(
            $action->execute($resolvedCountry, (string) $request->route('code')),
        );
    }
}
