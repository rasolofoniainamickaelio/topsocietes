<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowCityAction
{
    public function execute(Country $country, string $slug): City
    {
        $city = City::query()
            ->where('country_id', $country->id)
            ->where('slug', $slug)
            ->with([
                'districts',
                'neighborLinks.neighborCity',
                'contents' => fn ($query) => $query->where('status', ContentStatus::Published)->orderBy('section'),
            ])
            ->first();

        if ($city === null) {
            throw new ModelNotFoundException;
        }

        return $city;
    }
}
