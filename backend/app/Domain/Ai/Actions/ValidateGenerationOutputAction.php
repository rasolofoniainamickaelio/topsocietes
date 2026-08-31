<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Data\ValidationReportData;
use App\Domain\Ai\Support\ForbiddenTopics;
use App\Domain\Content\Models\Fact;
use Illuminate\Support\Collection;

/**
 * Contrôle post-génération (Phase 11) : deux vérifications indépendantes,
 * chacune suffisante pour faire échouer la validation. Le sentinel
 * `INSUFFICIENT_DATA` (consigne du prompt système) est traité à part —
 * ce n'est pas un échec de contrôle, c'est le modèle qui a correctement
 * refusé d'inventer.
 */
class ValidateGenerationOutputAction
{
    private const INSUFFICIENT_DATA_SENTINEL = 'INSUFFICIENT_DATA';

    /**
     * @param  Collection<int, Fact>  $facts
     */
    public function execute(string $output, Collection $facts): ValidationReportData
    {
        if (trim($output) === self::INSUFFICIENT_DATA_SENTINEL) {
            return new ValidationReportData(passed: false, insufficientData: true, issues: []);
        }

        $issues = [];

        $forbiddenTopic = ForbiddenTopics::firstMatch($output);

        if ($forbiddenTopic !== null) {
            $issues[] = "thème interdit détecté : {$forbiddenTopic}";
        }

        $unsourcedNumbers = $this->findUnsourcedNumbers($output, $facts);

        if ($unsourcedNumbers !== []) {
            $issues[] = 'nombre(s) non sourcé(s) : '.implode(', ', $unsourcedNumbers);
        }

        return new ValidationReportData(passed: $issues === [], insufficientData: false, issues: $issues);
    }

    /**
     * @param  Collection<int, Fact>  $facts
     * @return array<int, string>
     */
    private function findUnsourcedNumbers(string $output, Collection $facts): array
    {
        preg_match_all('/\d[\d\s.,]*\d|\d/', $output, $matches);

        // Les nombres à 1-2 chiffres sont trop bruyants (numéros de liste,
        // "3ᵉ arrondissement"…) pour ce contrôle — seuls les nombres à 3
        // chiffres ou plus (populations, superficies, années) sont vérifiés.
        $numbersInOutput = collect($matches[0])
            ->map(fn (string $n) => preg_replace('/[\s.,]/', '', $n))
            ->filter(fn (string $n) => $n !== '' && strlen($n) >= 3)
            ->unique();

        $sourceText = $facts->pluck('value')->filter()->implode(' ');

        return $numbersInOutput
            ->reject(fn (string $number) => str_contains($sourceText, $number))
            ->values()
            ->all();
    }
}
