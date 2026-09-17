<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Models\CityContent;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\EvaluatePagePublicationAction;
use App\Domain\Seo\Enums\NoindexReason;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Enums\PublicationDecision;
use App\Domain\Seo\Models\PagePublicationRule;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;

beforeEach(function (): void {
    $this->country = Country::factory()->create();
});

it('publishes a route with no matching rule by default', function (): void {
    $company = Company::factory()->for($this->country)->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
    ]);
    $route = PageRoute::factory()->for($this->country)->create([
        'entity_type' => 'company',
        'entity_id' => $company->id,
        'page_type' => PageType::Company,
    ]);

    $decision = app(EvaluatePagePublicationAction::class)->execute($route);

    expect($decision->decision)->toBe(PublicationDecision::Publish)
        ->and($decision->score)->toBe(100);
    expect($route->fresh()->is_indexable)->toBeTrue();
});

it('noindexes a company route when the company itself is not published', function (): void {
    $company = Company::factory()->for($this->country)->create([
        'content_status' => CompanyContentStatus::Pending,
    ]);
    $route = PageRoute::factory()->for($this->country)->create([
        'entity_type' => 'company',
        'entity_id' => $company->id,
        'page_type' => PageType::Company,
        'is_indexable' => true,
    ]);

    $decision = app(EvaluatePagePublicationAction::class)->execute($route);

    expect($decision->decision)->toBe(PublicationDecision::Noindex);
    expect($route->fresh())
        ->is_indexable->toBeFalse()
        ->noindex_reason->toBe(NoindexReason::Unpublished);
});

it('noindexes a city route below the configured company-count threshold', function (): void {
    PagePublicationRule::factory()->create([
        'page_type' => PageType::City,
        'country_id' => null,
        'min_companies' => 5,
        'min_facts' => 0,
        'min_content_sections' => 0,
        'min_word_count' => 0,
    ]);
    $city = City::factory()->for($this->country)->create(['companies_count' => 2]);
    $route = PageRoute::factory()->for($this->country)->create([
        'entity_type' => 'city',
        'entity_id' => $city->id,
        'page_type' => PageType::City,
    ]);

    $decision = app(EvaluatePagePublicationAction::class)->execute($route);

    expect($decision->decision)->toBe(PublicationDecision::Noindex)
        ->and($decision->reasons[0])->toContain('min_companies');
    expect($route->fresh()->noindex_reason)->toBe(NoindexReason::BelowPublicationThreshold);
});

it('publishes a city route that meets every configured threshold', function (): void {
    PagePublicationRule::factory()->create([
        'page_type' => PageType::City,
        'country_id' => null,
        'min_companies' => 1,
        'min_facts' => 0,
        'min_content_sections' => 1,
        'min_word_count' => 3,
    ]);
    $city = City::factory()->for($this->country)->create(['companies_count' => 10]);
    CityContent::factory()->for($city)->create(['status' => ContentStatus::Published, 'body' => 'Un texte suffisamment long ici']);
    $route = PageRoute::factory()->for($this->country)->create([
        'entity_type' => 'city',
        'entity_id' => $city->id,
        'page_type' => PageType::City,
    ]);

    $decision = app(EvaluatePagePublicationAction::class)->execute($route);

    expect($decision->decision)->toBe(PublicationDecision::Publish);
    expect($route->fresh()->is_indexable)->toBeTrue();
});

it('noindexes an activity route below the configured company-count threshold', function (): void {
    PagePublicationRule::factory()->create([
        'page_type' => PageType::Activity,
        'country_id' => null,
        'min_companies' => 5,
    ]);
    $activity = Activity::factory()->create(['companies_count' => 1]);
    $route = PageRoute::factory()->for($this->country)->create([
        'entity_type' => 'activity',
        'entity_id' => $activity->id,
        'page_type' => PageType::Activity,
    ]);

    $decision = app(EvaluatePagePublicationAction::class)->execute($route);

    expect($decision->decision)->toBe(PublicationDecision::Noindex);
});

it('publishes a sector route that meets its configured threshold', function (): void {
    PagePublicationRule::factory()->create([
        'page_type' => PageType::Sector,
        'country_id' => null,
        'min_companies' => 1,
        'min_facts' => 0,
        'min_content_sections' => 1,
        'min_word_count' => 3,
    ]);
    $sector = Sector::factory()->create(['companies_count' => 10]);
    ActivityContent::factory()->forSector()->create([
        'sector_id' => $sector->id,
        'status' => ContentStatus::Published,
        'body' => 'Un texte suffisamment long ici',
    ]);
    $route = PageRoute::factory()->for($this->country)->create([
        'entity_type' => 'sector',
        'entity_id' => $sector->id,
        'page_type' => PageType::Sector,
    ]);

    $decision = app(EvaluatePagePublicationAction::class)->execute($route);

    expect($decision->decision)->toBe(PublicationDecision::Publish);
});

it('prefers a country-specific rule over the generic one', function (): void {
    PagePublicationRule::factory()->create([
        'page_type' => PageType::City,
        'country_id' => null,
        'min_companies' => 1,
    ]);
    PagePublicationRule::factory()->create([
        'page_type' => PageType::City,
        'country_id' => $this->country->id,
        'min_companies' => 100,
    ]);
    $city = City::factory()->for($this->country)->create(['companies_count' => 10]);
    $route = PageRoute::factory()->for($this->country)->create([
        'entity_type' => 'city',
        'entity_id' => $city->id,
        'page_type' => PageType::City,
    ]);

    $decision = app(EvaluatePagePublicationAction::class)->execute($route);

    expect($decision->decision)->toBe(PublicationDecision::Noindex);
});
