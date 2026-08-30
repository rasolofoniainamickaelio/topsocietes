<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Content\Queries\DistrictContentBlocksQuery;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Models\District;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowDistrictAction
{
    public function __construct(private readonly DistrictContentBlocksQuery $contentBlocksQuery) {}

    public function execute(Country $country, string $slug): District
    {
        $district = District::query()
            ->where('country_id', $country->id)
            ->where('slug', $slug)
            ->with(['city'])
            ->first();

        if ($district === null) {
            throw new ModelNotFoundException;
        }

        $district->setRelation('contents', $this->contentBlocksQuery->execute($district));

        return $district;
    }
}
