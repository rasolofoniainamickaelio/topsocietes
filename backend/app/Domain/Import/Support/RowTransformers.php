<?php

declare(strict_types=1);

namespace App\Domain\Import\Support;

use Carbon\Carbon;
use Throwable;

/**
 * Registre nommé de transformateurs appliqués à une valeur brute mappée,
 * référencés par leur nom dans `import_mappings.transformers`
 * (`{"national_id": "digits_only"}`). Volontairement petit : un nouveau
 * besoin de normalisation ajoute un `case` ici, jamais une classe dédiée.
 *
 * Cas particulier : un `column_map` peut mapper la colonne source spéciale
 * `__row__` (au lieu d'un nom de colonne réel) vers un champ cible — le
 * transformer associé reçoit alors la ligne brute complète via
 * `applyRow()`, pour les cas où une valeur dérivée doit combiner plusieurs
 * colonnes (ex. `morocco_synthetic_id`, cf. Maroc : pas d'identifiant
 * officiel dans le fichier source, voir `MoroccoLocationLabels`).
 */
class RowTransformers
{
    public function apply(string $transformer, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($transformer) {
            'trim' => trim($value) ?: null,
            'upper' => mb_strtoupper(trim($value)),
            'digits_only' => preg_replace('/\D+/', '', $value) ?: null,
            'date_ymd' => $this->parseDate($value),
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>  $rawRow
     */
    public function applyRow(string $transformer, array $rawRow): ?string
    {
        return match ($transformer) {
            'morocco_synthetic_id' => $this->moroccoSyntheticId($rawRow),
            'morocco_city_slug' => $this->moroccoCitySlug($rawRow),
            'morocco_district_slug' => $this->moroccoDistrictSlug($rawRow),
            default => null,
        };
    }

    /**
     * Aucun ICE dans `docs/MAROC_ENTREPRISE.xlsx` : hash stable de
     * nom+adresse (jamais affiché comme un identifiant officiel — cf.
     * anti-hallucination CLAUDE.md §6.7). Sans l'adresse, des milliers de
     * lignes qui ne partagent que "Casablanca" comme adresse fusionneraient
     * à tort lors de l'upsert.
     *
     * @param  array<string, mixed>  $rawRow
     */
    private function moroccoSyntheticId(array $rawRow): ?string
    {
        $name = trim((string) ($rawRow["Nom d'entreprise"] ?? ''));
        $address = trim((string) ($rawRow['Adresse'] ?? ''));

        if ($name === '') {
            return null;
        }

        $normalized = mb_strtolower($name.'|'.$address);

        return 'MA-'.substr(sha1($normalized), 0, 16);
    }

    /**
     * @param  array<string, mixed>  $rawRow
     */
    private function moroccoCitySlug(array $rawRow): ?string
    {
        $resolved = MoroccoAddressResolver::resolve((string) ($rawRow['Adresse'] ?? ''));

        return $resolved?->citySlug;
    }

    /**
     * @param  array<string, mixed>  $rawRow
     */
    private function moroccoDistrictSlug(array $rawRow): ?string
    {
        $resolved = MoroccoAddressResolver::resolve((string) ($rawRow['Adresse'] ?? ''));

        return $resolved?->districtSlug;
    }

    private function parseDate(string $value): ?string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
