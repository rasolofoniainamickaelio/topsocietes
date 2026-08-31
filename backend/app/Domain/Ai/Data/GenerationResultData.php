<?php

declare(strict_types=1);

namespace App\Domain\Ai\Data;

use Spatie\LaravelData\Data;

/**
 * Résultat d'un `AiDriver` — jamais d'exception pour un échec fournisseur
 * (timeout, erreur HTTP, réponse malformée) : `success=false` avec
 * `errorMessage`, comme les connecteurs de la Phase 09.
 */
class GenerationResultData extends Data
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $rawOutput = null,
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
        public readonly ?int $costCents = null,
        public readonly ?string $errorMessage = null,
    ) {}
}
