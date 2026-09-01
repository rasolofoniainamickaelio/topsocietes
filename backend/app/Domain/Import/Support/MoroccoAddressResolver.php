<?php

declare(strict_types=1);

namespace App\Domain\Import\Support;

use Illuminate\Support\Str;

/**
 * Résout la colonne "Adresse" de `docs/MAROC_ENTREPRISE.xlsx` vers un
 * couple (slug ville, slug quartier) — jamais vers une création de
 * `City`/`District` : ces entités sont pré-créées par `MoroccoCitySeeder`
 * (cf. l'échange qui a motivé ce choix — créer à la volée pendant un
 * import parallélisé par lots exposerait à des races conditions sur des
 * contraintes d'unicité). Une adresse non reconnue retourne `null` :
 * l'entreprise s'importe quand même, simplement sans ville rattachée.
 *
 * Deux formats coexistent dans le fichier (voir `MoroccoLocationLabels`) :
 * multi-ligne (rue / région / ville - code postal - pays) et une seule
 * ligne ("...adresse... - Étiquette Maroc") où l'étiquette finale est un
 * libellé administratif OMPIC couvert par `LABEL_MAP`.
 */
final class MoroccoAddressResolver
{
    /**
     * Variantes de nom de ville rencontrées dans le format multi-ligne
     * (souvent en anglais) qui ne correspondent pas au slug du nom
     * canonique utilisé par `MoroccoCitySeeder`.
     *
     * @var array<string, string>
     */
    private const MULTILINE_CITY_ALIASES = [
        'tangier' => 'tanger',
        'marrakesh' => 'marrakech',
        'fes' => 'fes',
        'meknes' => 'meknes',
        'tetouan' => 'tetouan',
        'laayoune' => 'laayoune',
    ];

    public static function resolve(string $rawAddress): ?ResolvedMoroccoLocation
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\r\n/', $rawAddress) ?: []),
            static fn (string $line): bool => $line !== '',
        ));

        if (count($lines) >= 2) {
            return self::resolveMultiLine($lines);
        }

        if (count($lines) === 1) {
            return self::resolveSingleLine($lines[0]);
        }

        return null;
    }

    /** @param  array<int, string>  $lines */
    private static function resolveMultiLine(array $lines): ?ResolvedMoroccoLocation
    {
        $lastLine = $lines[count($lines) - 1];
        $parts = array_map('trim', explode(' - ', $lastLine));

        if (count($parts) < 2) {
            return null;
        }

        $citySlug = Str::slug($parts[0]);
        $alias = self::MULTILINE_CITY_ALIASES[$citySlug] ?? null;

        return new ResolvedMoroccoLocation($alias ?? $citySlug, null);
    }

    private static function resolveSingleLine(string $line): ?ResolvedMoroccoLocation
    {
        $noCountry = preg_replace('/\s*(Maroc|Morocco)\s*$/u', '', $line);
        $segments = array_map('trim', explode(' - ', trim((string) $noCountry)));
        $label = $segments[count($segments) - 1];

        $entry = MoroccoLocationLabels::labelMap()[$label] ?? null;

        if ($entry === null) {
            return null;
        }

        return new ResolvedMoroccoLocation(
            Str::slug($entry['city']),
            $entry['district'] !== null ? Str::slug($entry['district']) : null,
        );
    }
}
