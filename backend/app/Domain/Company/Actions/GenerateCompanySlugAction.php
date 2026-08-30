<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Support\Str;

/**
 * Slug unique par pays (CLAUDE.md §6.4 : immuable une fois attribué, tout
 * changement passe par une redirection 301 — hors périmètre de cette
 * Action, qui ne fait qu'attribuer le premier slug à la création).
 */
class GenerateCompanySlugAction
{
    public function execute(Country $country, string $legalName, ?City $city = null): string
    {
        $base = Str::slug($city !== null ? "{$legalName}-{$city->name}" : $legalName);

        $slug = $base;
        $suffix = 2;

        while (Company::query()->where('country_id', $country->id)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
