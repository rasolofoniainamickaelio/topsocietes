<?php

declare(strict_types=1);

use App\Domain\Ai\Actions\CheckAiBudgetAction;
use App\Domain\Ai\Actions\RecordAiSpendAction;
use App\Domain\Ai\Models\AiBudget;
use App\Domain\Geo\Models\Country;

it('allows generation when no budget row exists for the country and month', function (): void {
    $country = Country::factory()->create();

    expect(app(CheckAiBudgetAction::class)->execute($country))->toBeTrue();
});

it('allows generation while spend stays below the configured cap', function (): void {
    $country = Country::factory()->create();
    AiBudget::factory()->create(['country_id' => $country->id, 'period' => now()->format('Y-m'), 'max_cost_cents' => 1000, 'spent_cents' => 500]);

    expect(app(CheckAiBudgetAction::class)->execute($country))->toBeTrue();
});

it('blocks generation once spend reaches the configured cap', function (): void {
    $country = Country::factory()->create();
    AiBudget::factory()->create(['country_id' => $country->id, 'period' => now()->format('Y-m'), 'max_cost_cents' => 1000, 'spent_cents' => 1000]);

    expect(app(CheckAiBudgetAction::class)->execute($country))->toBeFalse();
});

it('records spend against an existing budget row without creating one when none is configured', function (): void {
    $country = Country::factory()->create();

    app(RecordAiSpendAction::class)->execute($country, 250);

    expect(AiBudget::query()->where('country_id', $country->id)->exists())->toBeFalse();
});

it('increments spent_cents on the existing budget row', function (): void {
    $country = Country::factory()->create();
    $budget = AiBudget::factory()->create(['country_id' => $country->id, 'period' => now()->format('Y-m'), 'max_cost_cents' => 1000, 'spent_cents' => 100]);

    app(RecordAiSpendAction::class)->execute($country, 50);

    expect($budget->fresh()->spent_cents)->toBe(150);
});
