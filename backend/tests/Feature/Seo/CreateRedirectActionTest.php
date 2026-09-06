<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\CreateRedirectAction;
use App\Domain\Seo\Enums\RedirectReason;
use App\Domain\Seo\Models\Redirect;

beforeEach(function (): void {
    $this->country = Country::factory()->create();
});

it('creates a simple redirect', function (): void {
    app(CreateRedirectAction::class)->execute($this->country, '/old', '/new', RedirectReason::SlugChange);

    $redirect = Redirect::query()->where('country_id', $this->country->id)->where('from_path', '/old')->firstOrFail();
    expect($redirect->to_path)->toBe('/new')
        ->and($redirect->status_code)->toBe(301)
        ->and($redirect->reason)->toBe(RedirectReason::SlugChange);
});

it('does nothing when the path does not actually change', function (): void {
    $result = app(CreateRedirectAction::class)->execute($this->country, '/same', '/same', RedirectReason::SlugChange);

    expect($result)->toBeNull();
    expect(Redirect::query()->count())->toBe(0);
});

it('collapses a chain by targeting the final destination directly, never A to B to C', function (): void {
    // /a -> /b existe déjà ; créer /new-source -> /a doit produire /new-source -> /b directement.
    Redirect::factory()->for($this->country)->create(['from_path' => '/a', 'to_path' => '/b']);

    app(CreateRedirectAction::class)->execute($this->country, '/new-source', '/a', RedirectReason::SlugChange);

    $redirect = Redirect::query()->where('from_path', '/new-source')->firstOrFail();
    expect($redirect->to_path)->toBe('/b');
});

it('repoints any existing redirect that targeted the old path, avoiding the reverse chain', function (): void {
    // /x -> /old-path existe déjà ; renommer /old-path -> /new-path doit
    // faire pointer /x directement vers /new-path, jamais /x -> /old-path -> /new-path.
    Redirect::factory()->for($this->country)->create(['from_path' => '/x', 'to_path' => '/old-path']);

    app(CreateRedirectAction::class)->execute($this->country, '/old-path', '/new-path', RedirectReason::SlugChange);

    $existing = Redirect::query()->where('from_path', '/x')->firstOrFail();
    expect($existing->to_path)->toBe('/new-path');

    $created = Redirect::query()->where('from_path', '/old-path')->firstOrFail();
    expect($created->to_path)->toBe('/new-path');
});

it('is idempotent: recreating the same redirect updates it rather than duplicating', function (): void {
    app(CreateRedirectAction::class)->execute($this->country, '/old', '/new', RedirectReason::SlugChange);
    app(CreateRedirectAction::class)->execute($this->country, '/old', '/newer', RedirectReason::SlugChange);

    expect(Redirect::query()->where('from_path', '/old')->count())->toBe(1);
    expect(Redirect::query()->where('from_path', '/old')->value('to_path'))->toBe('/newer');
});
