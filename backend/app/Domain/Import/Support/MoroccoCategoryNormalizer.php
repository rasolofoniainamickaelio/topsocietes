<?php

declare(strict_types=1);

namespace App\Domain\Import\Support;

use Illuminate\Support\Str;

/**
 * Normalise le texte libre de la colonne "Catégories" de
 * `docs/MAROC_ENTREPRISE.xlsx` vers une clé stable, utilisée à la fois
 * pour construire la liste d'activités du seeder (`MoroccoActivitySeeder`)
 * et pour résoudre l'activité d'une ligne au moment de l'import — les deux
 * DOIVENT utiliser exactement cet algorithme pour rester cohérents.
 *
 * Le texte source contient des artefacts d'export ("Secteur d'activité :"
 * suivi de séquences `_xHHHH_`, tabulations/retours ligne résiduels) et
 * une forte fragmentation par variantes (accents, singulier/pluriel,
 * ponctuation finale, préfixes "Entrepreneur de…"/"Tenant…"). L'objectif
 * n'est pas une taxonomie propre mais un regroupement suffisant pour
 * éviter de créer une `Activity` par faute de frappe.
 */
final class MoroccoCategoryNormalizer
{
    public static function clean(string $raw): string
    {
        $s = preg_replace("/^Secteur d'activit[eé]\s*:\s*/ui", '', $raw) ?? $raw;
        $s = preg_replace('/_x[0-9A-Fa-f]{4}_/', ' ', $s) ?? $s;
        $s = preg_replace('/[\x00-\x1F\x7F]/', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        $s = str_replace('&amp;', '&', $s);

        return trim($s);
    }

    /**
     * @return string clé normalisée, stockée telle quelle dans `activities.code`
     */
    public static function key(string $raw): string
    {
        $s = Str::lower(Str::ascii(self::clean($raw)));
        $s = preg_replace('/^[-\s]+/', '', $s) ?? $s;
        $s = preg_replace('/\.+$/', '', $s) ?? $s;
        $s = preg_replace("/^(le |la |les |l')/", '', $s) ?? $s;
        $s = preg_replace("/^(entrepreneur de |entrepreneur d'|entrep de |entreprise de |tenant (une |un )?|tenant agence)/", '', $s) ?? $s;
        $s = preg_replace('/^(agence de |agences de )/', 'agence ', $s) ?? $s;
        $s = preg_replace("/ pour le compte d'autrui\.?$/", '', $s) ?? $s;
        $s = preg_replace('/ pour autrui\.?$/', '', $s) ?? $s;
        $s = preg_replace('/s\b/', '', $s) ?? $s;
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s) ?? $s;

        return trim($s);
    }
}
