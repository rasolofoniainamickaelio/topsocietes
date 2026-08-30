<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Content\Models\ContentSection;
use App\Domain\Content\Queries\ResolveAdminDivisionContentQuery;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowAdminDivisionAction
{
    public function __construct(private readonly ResolveAdminDivisionContentQuery $resolveQuery) {}

    public function execute(Country $country, string $slug): AdminDivision
    {
        $division = AdminDivision::query()
            ->where('country_id', $country->id)
            ->where('slug', $slug)
            ->with('children')
            ->first();

        if ($division === null) {
            throw new ModelNotFoundException;
        }

        $direct = AdminDivisionContent::query()
            ->where('admin_division_id', $division->id)
            ->where('status', ContentStatus::Published)
            ->get();

        $enabledSections = ContentSection::query()
            ->where('scope', ContentSectionScope::AdminDivision)
            ->where('is_enabled', true)
            ->pluck('key')
            ->all();

        $missing = array_values(array_diff($enabledSections, $direct->pluck('section')->all()));

        $blocks = $missing === []
            ? $direct
            : $direct->concat($this->resolveQuery->execute($division->parent, $missing));

        $division->setRelation('contents', $blocks);

        return $division;
    }
}
