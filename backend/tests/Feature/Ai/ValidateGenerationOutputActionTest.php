<?php

declare(strict_types=1);

use App\Domain\Ai\Actions\ValidateGenerationOutputAction;
use App\Domain\Content\Models\Fact;
use Illuminate\Support\Collection;

it('passes clean text that only uses sourced numbers', function (): void {
    $facts = new Collection([Fact::factory()->make(['key' => 'population', 'value' => '500716'])]);

    $report = app(ValidateGenerationOutputAction::class)->execute(
        'Lyon compte 500716 habitants selon les derniers chiffres disponibles.',
        $facts,
    );

    expect($report->passed)->toBeTrue()
        ->and($report->insufficientData)->toBeFalse()
        ->and($report->issues)->toBeEmpty();
});

it('recognizes the INSUFFICIENT_DATA sentinel as a deliberate decline, not a validation failure', function (): void {
    $report = app(ValidateGenerationOutputAction::class)->execute('INSUFFICIENT_DATA', new Collection);

    expect($report->passed)->toBeFalse()
        ->and($report->insufficientData)->toBeTrue()
        ->and($report->issues)->toBeEmpty();
});

it('rejects text mentioning a forbidden topic', function (): void {
    $facts = new Collection([Fact::factory()->make(['key' => 'population', 'value' => '500716'])]);

    $report = app(ValidateGenerationOutputAction::class)->execute(
        "Cette entreprise jouit d'une excellente réputation dans la région.",
        $facts,
    );

    expect($report->passed)->toBeFalse()
        ->and($report->insufficientData)->toBeFalse()
        ->and($report->issues[0])->toContain('réputation');
});

it('rejects text naming a company director', function (): void {
    $facts = new Collection([Fact::factory()->make(['key' => 'population', 'value' => '500716'])]);

    $report = app(ValidateGenerationOutputAction::class)->execute(
        "Le dirigeant de l'entreprise a fondé la société en 1998.",
        $facts,
    );

    expect($report->passed)->toBeFalse()
        ->and($report->issues[0])->toContain('dirigeants');
});

it('rejects a number that does not appear in any provided fact', function (): void {
    $facts = new Collection([Fact::factory()->make(['key' => 'population', 'value' => '500716'])]);

    $report = app(ValidateGenerationOutputAction::class)->execute(
        "Lyon a été fondée en l'an 43 avant notre ère, il y a 2067 ans.",
        $facts,
    );

    expect($report->passed)->toBeFalse()
        ->and($report->issues[0])->toContain('2067');
});

it('ignores short 1-2 digit numbers to avoid noise', function (): void {
    $facts = new Collection([Fact::factory()->make(['key' => 'population', 'value' => '500716'])]);

    $report = app(ValidateGenerationOutputAction::class)->execute(
        'Lyon est composée de 9 arrondissements, dont le 3e est le plus peuplé.',
        $facts,
    );

    expect($report->passed)->toBeTrue();
});
