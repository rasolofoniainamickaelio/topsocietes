<?php

declare(strict_types=1);

namespace App\Domain\Ai\Enums;

/**
 * `Create` génère (ou régénère intégralement) une section — c'est le seul
 * mode que `ContentGenerationPipeline` traite en écriture directe. Les
 * trois autres transforment un contenu déjà publié/généré et n'écrivent
 * jamais une nouvelle ligne, toujours en statut `Review` (Phase 10,
 * "réécriture / résumé / contextualisation").
 */
enum GenerationMode: string
{
    case Create = 'create';
    case Rewrite = 'rewrite';
    case Summarize = 'summarize';
    case Contextualize = 'contextualize';
}
