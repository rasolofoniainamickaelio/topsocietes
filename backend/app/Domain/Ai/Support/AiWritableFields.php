<?php

declare(strict_types=1);

namespace App\Domain\Ai\Support;

use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\DistrictContent;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Verrou structurel du CLAUDE.md §6.7 : le pipeline IA n'a le droit d'écrire
 * QUE le champ `body` d'un contenu territorial/sectoriel mutualisé, jamais
 * une colonne de `Company`/`Establishment` ni un fait structuré. Cette liste
 * est fermée à dessein — un nouveau type de cible generative doit être ajouté
 * ici explicitement, jamais deviné dynamiquement.
 */
final class AiWritableFields
{
    /** @var array<int, class-string<Model>> */
    private const ALLOWED_TARGET_MODELS = [
        CityContent::class,
        DistrictContent::class,
        AdminDivisionContent::class,
        ActivityContent::class,
        CityActivityContent::class,
    ];

    public static function assertWritable(Model $content): void
    {
        if (! in_array($content::class, self::ALLOWED_TARGET_MODELS, true)) {
            throw new LogicException(
                'Le pipeline IA a tenté d\'écrire sur '.$content::class.', hors de la liste blanche des contenus mutualisés autorisés.',
            );
        }
    }
}
