<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Support;

/**
 * Liste fermée des colonnes `Company` qu'une contestation acceptée a le
 * droit de corriger (Phase 22) — jamais un champ structurel/dérivé (slug,
 * statut, identifiants géographiques, scores) qui a ses propres règles de
 * calcul et redirections. Utilisée pour l'affichage (visibilité du bouton
 * Filament) ; l'écriture elle-même se fait via le `match` explicite et
 * entièrement typé de `ApplyDisputeCorrectionAction`, jamais par une
 * affectation dynamique `[$field => $value]` que PHPStan ne peut pas
 * vérifier statiquement contre les colonnes de `Company`. Un nouveau champ
 * corrigible s'ajoute aux deux endroits à la fois, jamais deviné depuis
 * `$fillable`.
 */
final class DisputeCorrectableFields
{
    /** @var array<int, string> */
    public const ALLOWED = [
        'legal_name',
        'trade_name',
        'legal_form_label',
        'headcount_range',
        'about_text',
    ];

    public static function isCorrectable(string $field): bool
    {
        return in_array($field, self::ALLOWED, true);
    }
}
