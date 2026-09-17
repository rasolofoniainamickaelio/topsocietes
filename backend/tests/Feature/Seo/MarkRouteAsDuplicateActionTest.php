<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\MarkRouteAsDuplicateAction;
use App\Domain\Seo\Enums\NoindexReason;
use App\Domain\Seo\Models\PageRoute;

it('marks a route as a non-indexable duplicate of another route in the same country', function (): void {
    $country = Country::factory()->create();
    $canonical = PageRoute::factory()->for($country)->create(['path' => '/villes/paris', 'is_indexable' => true]);
    $duplicate = PageRoute::factory()->for($country)->create(['path' => '/paris', 'is_indexable' => true]);

    $result = app(MarkRouteAsDuplicateAction::class)->execute($duplicate, $canonical);

    expect($result->canonical_route_id)->toBe($canonical->id)
        ->and($result->is_indexable)->toBeFalse()
        ->and($result->noindex_reason)->toBe(NoindexReason::Duplicate);
});

it('rejects marking a route as a duplicate of itself', function (): void {
    $route = PageRoute::factory()->create();

    app(MarkRouteAsDuplicateAction::class)->execute($route, $route);
})->throws(InvalidArgumentException::class);

it('rejects a canonical route from another country', function (): void {
    $duplicate = PageRoute::factory()->create();
    $canonical = PageRoute::factory()->create();

    app(MarkRouteAsDuplicateAction::class)->execute($duplicate, $canonical);
})->throws(InvalidArgumentException::class);
