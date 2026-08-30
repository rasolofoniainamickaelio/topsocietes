<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Actions;

use App\Domain\Content\Queries\ActivityContentBlocksQuery;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowActivityAction
{
    public function __construct(private readonly ActivityContentBlocksQuery $contentBlocksQuery) {}

    public function execute(Country $country, string $slug): Activity
    {
        // Même scoping que ListActivitiesAction (nomenclature.country_id),
        // pour rester cohérent avec l'index existant.
        $activity = Activity::query()
            ->whereHas('nomenclature', fn ($query) => $query->where('country_id', $country->id))
            ->where('slug', $slug)
            ->with('sectors')
            ->first();

        if ($activity === null) {
            throw new ModelNotFoundException;
        }

        $activity->setRelation('contents', $this->contentBlocksQuery->forActivity($activity, $country));

        return $activity;
    }
}
