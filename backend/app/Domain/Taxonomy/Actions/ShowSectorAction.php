<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Actions;

use App\Domain\Content\Queries\ActivityContentBlocksQuery;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowSectorAction
{
    public function __construct(private readonly ActivityContentBlocksQuery $contentBlocksQuery) {}

    public function execute(Country $country, string $slug): Sector
    {
        // Les secteurs ne sont pas scopés par pays (ListSectorsAction) :
        // seul leur contenu (activity_contents.country_id) l'est.
        $sector = Sector::query()->where('slug', $slug)->first();

        if ($sector === null) {
            throw new ModelNotFoundException;
        }

        $sector->setRelation('contents', $this->contentBlocksQuery->forSector($sector, $country));

        return $sector;
    }
}
