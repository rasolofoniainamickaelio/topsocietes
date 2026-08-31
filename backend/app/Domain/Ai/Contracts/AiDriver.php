<?php

declare(strict_types=1);

namespace App\Domain\Ai\Contracts;

use App\Domain\Ai\Data\GenerationRequestData;
use App\Domain\Ai\Data\GenerationResultData;
use App\Domain\Ai\Enums\AiProvider;

/**
 * Couche d'abstraction indépendante du fournisseur (Phase 10) : changer
 * de modèle/fournisseur ne touche jamais le code métier, seulement le
 * driver injecté.
 */
interface AiDriver
{
    public function provider(): AiProvider;

    public function generate(GenerationRequestData $request): GenerationResultData;
}
