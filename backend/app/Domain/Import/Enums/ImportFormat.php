<?php

declare(strict_types=1);

namespace App\Domain\Import\Enums;

enum ImportFormat: string
{
    case Csv = 'csv';

    /**
     * JSON Lines (un objet JSON par ligne), jamais un unique tableau JSON :
     * seul ce format permet à `ProcessImportChunkAction` de lire un lot sans
     * jamais charger le fichier entier en mémoire (Phase 03, "traitement par
     * lots... jamais tout en mémoire").
     */
    case Json = 'json';
    case Xml = 'xml';
}
