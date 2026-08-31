<?php

declare(strict_types=1);

namespace App\Domain\Ai\Data;

use Spatie\LaravelData\Data;

/**
 * Requête envoyée à un `AiDriver` — déjà assemblée (prompt système +
 * prompt utilisateur avec faits injectés) par l'Action orchestratrice,
 * le driver ne fait qu'appeler le fournisseur.
 */
class GenerationRequestData extends Data
{
    public function __construct(
        public readonly string $systemPrompt,
        public readonly string $userPrompt,
        public readonly string $model,
    ) {}
}
