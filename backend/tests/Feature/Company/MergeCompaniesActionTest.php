<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Actions\MergeCompaniesAction;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyClaim;
use App\Domain\Company\Models\CompanyContact;
use App\Domain\Company\Models\Establishment;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\RedirectReason;
use App\Domain\Seo\Models\Redirect;

it('transfers establishments, contacts, claims and subscriptions to the target then archives the source', function (): void {
    $country = Country::factory()->create();
    $source = Company::factory()->for($country)->create();
    $target = Company::factory()->for($country)->create();

    $establishment = Establishment::factory()->for($source)->create();
    $contact = CompanyContact::factory()->for($source)->create();
    $claim = CompanyClaim::factory()->for($source)->create();
    $subscription = Subscription::factory()->for($source)->create();

    $result = app(MergeCompaniesAction::class)->execute($source, $target);

    expect($result->id)->toBe($target->id);

    expect($establishment->fresh()->company_id)->toBe($target->id)
        ->and($contact->fresh()->company_id)->toBe($target->id)
        ->and($claim->fresh()->company_id)->toBe($target->id)
        ->and($subscription->fresh()->company_id)->toBe($target->id);

    expect(Company::query()->find($source->id))->toBeNull();
    expect(Company::withTrashed()->find($source->id)->trashed())->toBeTrue();
});

it('creates a permanent redirect from the source path to the target path', function (): void {
    $country = Country::factory()->create();
    $source = Company::factory()->for($country)->create(['slug' => 'doublon']);
    $target = Company::factory()->for($country)->create(['slug' => 'canonique']);

    app(MergeCompaniesAction::class)->execute($source, $target);

    $redirect = Redirect::query()->where('country_id', $country->id)->where('from_path', 'LIKE', '%doublon%')->firstOrFail();

    expect($redirect->to_path)->toContain('canonique')
        ->and($redirect->reason)->toBe(RedirectReason::Merge)
        ->and($redirect->status_code)->toBe(301);
});

it('refuses to merge a company with itself', function (): void {
    $company = Company::factory()->create();

    app(MergeCompaniesAction::class)->execute($company, $company);
})->throws(InvalidArgumentException::class);

it('refuses to merge companies from different countries', function (): void {
    $source = Company::factory()->create();
    $target = Company::factory()->create();

    app(MergeCompaniesAction::class)->execute($source, $target);
})->throws(InvalidArgumentException::class);
