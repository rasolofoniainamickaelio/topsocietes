<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\SyncPageRouteAction;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;

it('creates a route for an entity that has none yet', function (): void {
    $country = Country::factory()->create();
    $company = Company::factory()->for($country)->create(['content_status' => CompanyContentStatus::Published, 'is_indexable' => true]);

    $route = app(SyncPageRouteAction::class)->execute($company, $country, PageType::Company, '/entreprise/acme');

    expect($route->path)->toBe('/entreprise/acme')
        ->and($route->entity_type)->toBe('company')
        ->and($route->entity_id)->toBe($company->id);
    expect(PageRoute::query()->where('entity_type', 'company')->where('entity_id', $company->id)->count())->toBe(1);
});

it('updates the existing route in place on a path change, never duplicating it', function (): void {
    $country = Country::factory()->create();
    $company = Company::factory()->for($country)->create();

    app(SyncPageRouteAction::class)->execute($company, $country, PageType::Company, '/entreprise/ancien');
    $route = app(SyncPageRouteAction::class)->execute($company, $country, PageType::Company, '/entreprise/nouveau');

    expect($route->path)->toBe('/entreprise/nouveau');
    expect(PageRoute::query()->where('entity_type', 'company')->where('entity_id', $company->id)->count())->toBe(1);
});

it('evaluates indexability immediately after syncing', function (): void {
    $country = Country::factory()->create();
    $company = Company::factory()->for($country)->create(['content_status' => CompanyContentStatus::Pending]);

    $route = app(SyncPageRouteAction::class)->execute($company, $country, PageType::Company, '/entreprise/acme');

    expect($route->is_indexable)->toBeFalse();
});
